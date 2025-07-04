# tests/python/test_llm_core_context.py
import pytest
import json
import re
import argparse  # Adicionado para Namespace
from pathlib import Path
from unittest.mock import patch, mock_open, call, MagicMock
from google.genai import types as genai_types
from typing import List, Optional, Dict, Any, Set, Union  # Adicionado Union

# Adiciona o diretório raiz do projeto ao sys.path para importações corretas
import sys

_project_root_dir_for_test = Path(__file__).resolve().parent.parent.parent
if str(_project_root_dir_for_test) not in sys.path:
    sys.path.insert(0, str(_project_root_dir_for_test))

from scripts.llm_core import context as core_context
from scripts.llm_core import config as core_config
from scripts.llm_core.context import FileProcessUnit  # Importa a dataclass
from scripts.llm_core.exceptions import MissingEssentialFileAbort  # AC4.1
from scripts.llm_core import io_utils  # AC4.1


# --- Testes para find_latest_context_dir ---
def test_find_latest_context_dir_no_dir(tmp_path: Path):
    assert core_context.find_latest_context_dir(tmp_path / "non_existent") is None


def test_find_latest_context_dir_empty_dir(tmp_path: Path):
    context_base = tmp_path / "context_base"
    context_base.mkdir()
    assert core_context.find_latest_context_dir(context_base) is None


def test_find_latest_context_dir_one_valid(tmp_path: Path):
    context_base = tmp_path / "context_base"
    context_base.mkdir()
    dir1 = context_base / "20230101_120000"
    dir1.mkdir()
    assert core_context.find_latest_context_dir(context_base) == dir1


def test_find_latest_context_dir_multiple_valid(tmp_path: Path):
    context_base = tmp_path / "context_base"
    context_base.mkdir()
    dir1 = context_base / "20230101_100000"
    dir1.mkdir()
    dir2_latest = context_base / "20230102_120000"
    dir2_latest.mkdir()
    dir3 = context_base / "20221231_235959"
    dir3.mkdir()
    assert core_context.find_latest_context_dir(context_base) == dir2_latest


def test_find_latest_context_dir_with_invalid_names(tmp_path: Path):
    context_base = tmp_path / "context_base"
    context_base.mkdir()
    dir_valid = context_base / "20230101_110000"
    dir_valid.mkdir()
    (context_base / "invalid_name").mkdir()
    (context_base / "20230101_1000").write_text("file, not dir")
    assert core_context.find_latest_context_dir(context_base) == dir_valid


# --- Testes para load_manifest ---
@patch("pathlib.Path.is_file")
@patch(
    "builtins.open",
    new_callable=mock_open,
    read_data='{"files": {"path/to/file.py": {"type": "code_python"}}}',
)
def test_load_manifest_success(mock_file_open, mock_path_is_file, tmp_path: Path):
    mock_path_is_file.return_value = True
    manifest_file = tmp_path / "data" / "20230101_120000_manifest.json"

    result = core_context.load_manifest(manifest_file)
    mock_path_is_file.assert_called_once_with()
    mock_file_open.assert_called_once_with(manifest_file, "r", encoding="utf-8")
    assert result is not None
    assert "files" in result
    assert "path/to/file.py" in result["files"]


@patch("pathlib.Path.is_file", return_value=False)
def test_load_manifest_file_not_found(mock_path_is_file, tmp_path: Path):
    non_existent_manifest = tmp_path / "ghost_manifest.json"
    result = core_context.load_manifest(non_existent_manifest)
    assert result is None
    mock_path_is_file.assert_called_once_with()


@patch("pathlib.Path.is_file", return_value=True)
@patch("builtins.open", new_callable=mock_open, read_data="invalid json")
def test_load_manifest_invalid_json(
    mock_file_invalid_json, mock_path_is_file, tmp_path: Path
):
    manifest_file = tmp_path / "invalid_format.json"
    result = core_context.load_manifest(manifest_file)
    assert result is None


@patch("pathlib.Path.is_file", return_value=True)
@patch("builtins.open", new_callable=mock_open, read_data='{"not_files_key": {}}')
def test_load_manifest_missing_files_key(
    mock_file_missing_key, mock_path_is_file, tmp_path: Path
):
    manifest_file = tmp_path / "missing_key.json"
    result = core_context.load_manifest(manifest_file)
    assert result is None



# Helper function likely defined in the original test file.
# This is a plausible implementation based on its usage in the failing test.
def _create_tmp_file_rel_to_project_root(project_root: Path, relative_path_str: str, content: str) -> Path:
    full_path = project_root / relative_path_str
    full_path.parent.mkdir(parents=True, exist_ok=True)
    full_path.write_text(content, encoding="utf-8")
    return full_path



def _check_loaded_parts(
    parts: List[genai_types.Part],
    expected_relative_paths_set: Set[str],
    manifest_data: Optional[Dict[str, Any]],
    project_root_for_test: Path,
):
    """
    Checks if the loaded parts match the expected relative paths and content.
    Compares sets for paths to be order-agnostic.
    """
    assert len(parts) == len(
        expected_relative_paths_set
    ), f"Expected {len(expected_relative_paths_set)} parts, got {len(parts)}"

    actual_relative_paths_set = set()
    for part in parts:
        assert hasattr(part, "text"), "Part is missing 'text' attribute"

        content_match = re.match(
            r"^--- (?:START OF ESSENTIAL FILE|START OF FILE) (.*?) ---\n(?:--- SUMMARY ---\n(.*?)\n--- END SUMMARY ---\n)?(.*?)\n?^--- (?:END OF ESSENTIAL FILE|END OF FILE) \1 ---$",
            part.text,
            re.DOTALL | re.MULTILINE,
        )
        assert content_match, f"Part text format is incorrect: {part.text[:300]}..."

        relative_path_str = content_match.group(1).strip()
        actual_summary_in_part = (
            content_match.group(2).strip() if content_match.group(2) else None
        )
        actual_content_in_part = content_match.group(3).strip()

        actual_relative_paths_set.add(relative_path_str)

        expected_file_path_abs = project_root_for_test / relative_path_str
        assert (
            expected_file_path_abs.is_file()
        ), f"Source file for part {relative_path_str} not found at {expected_file_path_abs}"

        is_summary_content = False
        if (
            manifest_data
            and "files" in manifest_data
            and relative_path_str in manifest_data["files"]
        ):
            meta = manifest_data["files"][relative_path_str]
            if (
                isinstance(meta, dict)
                and meta.get("summary")
                and meta.get("summary").strip() == actual_content_in_part
            ):
                is_summary_content = True

        if (
            not is_summary_content
            and not (
                "... [CONTEÚDO TRUNCADO PARA CABER NO LIMITE DE TOKENS] ..."
                in actual_content_in_part
            )
            and not ("...[TRUNCADO]..." in actual_content_in_part)
        ):
            expected_content = expected_file_path_abs.read_text(
                encoding="utf-8", errors="ignore"
            ).strip()
            assert (
                actual_content_in_part == expected_content
            ), f"Content mismatch for {relative_path_str}.\nExpected:\n'''{expected_content}'''\nGot:\n'''{actual_content_in_part}'''"

        if manifest_data and "files" in manifest_data and not is_summary_content:
            expected_summary_from_manifest = (
                manifest_data["files"].get(relative_path_str, {}).get("summary")
            )
            if expected_summary_from_manifest:
                assert (
                    actual_summary_in_part == expected_summary_from_manifest.strip()
                ), f"Summary mismatch for {relative_path_str}"
            else:
                assert (
                    actual_summary_in_part is None
                ), f"Unexpected summary found for {relative_path_str} when none expected from manifest"
        elif actual_summary_in_part is not None and not is_summary_content:
            pass

    assert (
        actual_relative_paths_set == expected_relative_paths_set
    ), f"Mismatch in loaded file paths. Expected {expected_relative_paths_set}, got {actual_relative_paths_set}"


@pytest.mark.parametrize(
    "scenario_name, primary_files_to_create, common_files_to_create, exclude_list, expected_loaded_paths_set, manifest_data, primary_context_dir_exists, common_context_dir_exists",
    [
        (
            "load_latest_only",
            {"latest_file1.txt": "latest1 content"},
            {},
            [],
            {"context_llm/code/{latest_dir_name}/latest_file1.txt"},
            None,
            True,
            False,
        ),
        (
            "load_common_only",
            {},
            {"common_fileA.txt": "commonA content"},
            [],
            {"context_llm/common/common_fileA.txt"},
            None,
            False,
            True,
        ),
        (
            "load_both_no_overlap",
            {"latest_file1.txt": "latest1 content"},
            {"common_fileA.txt": "commonA content"},
            [],
            {
                "context_llm/code/{latest_dir_name}/latest_file1.txt",
                "context_llm/common/common_fileA.txt",
            },
            None,
            True,
            True,
        ),
        (
            "load_with_primary_exclusions",
            {"latest_file1.txt": "latest1", "latest_file2.txt": "latest2_excl_content"},
            {"common_file1.txt": "common1"},
            ["context_llm/code/{latest_dir_name}/latest_file2.txt"],
            {
                "context_llm/common/common_file1.txt",
                "context_llm/code/{latest_dir_name}/latest_file1.txt",
            },
            None,
            True,
            True,
        ),
        (
            "load_with_common_exclusions",
            {"latest_file1.txt": "latest1"},
            {"common_file1.txt": "common1_excl_content", "common_file2.txt": "common2"},
            ["context_llm/common/common_file1.txt"],
            {
                "context_llm/common/common_file2.txt",
                "context_llm/code/{latest_dir_name}/latest_file1.txt",
            },
            None,
            True,
            True,
        ),
        (
            "load_with_mixed_exclusions",
            {"latest_file1.txt": "latest1_excl_content", "latest_file2.txt": "latest2"},
            {"common_file1.txt": "common1_excl_content", "common_file2.txt": "common2"},
            [
                "context_llm/code/{latest_dir_name}/latest_file1.txt",
                "context_llm/common/common_file1.txt",
            ],
            {
                "context_llm/common/common_file2.txt",
                "context_llm/code/{latest_dir_name}/latest_file2.txt",
            },
            None,
            True,
            True,
        ),
        (
            "load_with_exclusions_and_manifest",
            {
                "latest_main.txt": "latest main",
                "latest_excl.txt": "latest excl content",
            },
            {
                "common_main.txt": "common main",
                "common_excl.txt": "common excl content",
            },
            [
                "context_llm/common/common_excl.txt",
                "context_llm/code/{latest_dir_name}/latest_excl.txt",
            ],
            {
                "context_llm/code/{latest_dir_name}/latest_main.txt",
                "context_llm/common/common_main.txt",
            },
            {
                "files": {
                    "context_llm/code/{latest_dir_name}/latest_main.txt": {
                        "summary": "Summary for latest main"
                    },
                    "context_llm/common/common_main.txt": {
                        "summary": "Summary for common main"
                    },
                    "context_llm/code/{latest_dir_name}/latest_excl.txt": {
                        "summary": "Summary for excluded latest"
                    },
                }
            },
            True,
            True,
        ),
    ],
)
def test_prepare_context_parts_default_loading(
    tmp_path: Path,
    monkeypatch,
    scenario_name: str,
    primary_files_to_create: Dict[str, str],
    common_files_to_create: Dict[str, str],
    exclude_list: List[str],
    expected_loaded_paths_set: Set[str],
    manifest_data: Optional[Dict[str, Any]],
    primary_context_dir_exists: bool,
    common_context_dir_exists: bool,
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)
    monkeypatch.setattr(
        core_config, "COMMON_CONTEXT_DIR", tmp_path / "context_llm" / "common"
    )

    latest_dir_name = "20230101_120000"
    primary_dir = tmp_path / "context_llm" / "code" / latest_dir_name
    common_dir = tmp_path / "context_llm" / "common"

    if primary_context_dir_exists:
        primary_dir.mkdir(parents=True, exist_ok=True)
        for fname, content in primary_files_to_create.items():
            _create_tmp_file_rel_to_project_root(
                tmp_path, f"context_llm/code/{latest_dir_name}/{fname}", content
            )

    if common_context_dir_exists:
        common_dir.mkdir(parents=True, exist_ok=True)
        for fname, content in common_files_to_create.items():
            _create_tmp_file_rel_to_project_root(
                tmp_path, f"context_llm/common/{fname}", content
            )

    formatted_exclude_list = [
        item.format(latest_dir_name=latest_dir_name) for item in exclude_list
    ]

    formatted_manifest_data = None
    if manifest_data and "files" in manifest_data:
        formatted_manifest_data = {"files": {}}
        for k, v in manifest_data["files"].items():
            formatted_k = k.format(latest_dir_name=latest_dir_name)
            formatted_manifest_data["files"][formatted_k] = v

    formatted_expected_paths_set = {
        s.format(latest_dir_name=latest_dir_name) for s in expected_loaded_paths_set
    }

    actual_loaded_parts = core_context.prepare_context_parts(
        primary_dir if primary_context_dir_exists else None,
        common_dir if common_context_dir_exists else None,
        exclude_list=formatted_exclude_list,
        manifest_data=formatted_manifest_data,
        include_list=None,
        verbose=True,
    )

    _check_loaded_parts(
        actual_loaded_parts,
        formatted_expected_paths_set,
        formatted_manifest_data,
        tmp_path,
    )


# --- Testes para _load_files_from_dir (que é chamado por prepare_context_parts) ---
def test_load_files_from_dir_basic_loading_and_exclusion(
    tmp_path: Path,
    monkeypatch,
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)
    test_dir = tmp_path / "test_context_dir"
    test_dir.mkdir()

    file1_content = "Content of file1"
    file2_content = "Content of file2 to be excluded"
    file3_content = "Content of file3.md"

    file1_rel_path_str = "test_context_dir/file1.txt"
    file2_rel_path_str = "test_context_dir/file2_excluded.txt"
    file3_rel_path_str = "test_context_dir/file3.md"
    file_ignored_ext_rel_path_str = "test_context_dir/file4.log"

    _create_tmp_file_rel_to_project_root(tmp_path, file1_rel_path_str, file1_content)
    _create_tmp_file_rel_to_project_root(tmp_path, file2_rel_path_str, file2_content)
    _create_tmp_file_rel_to_project_root(tmp_path, file3_rel_path_str, file3_content)
    _create_tmp_file_rel_to_project_root(
        tmp_path, file_ignored_ext_rel_path_str, "log content"
    )

    processed_units_list: List[FileProcessUnit] = []
    exclude_set_for_test = {file2_rel_path_str}
    essential_map_paths_for_test: Set[str] = set()
    skip_if_already_loaded_for_test: Set[str] = set()  # Novo argumento

    core_context._load_files_from_dir(
        test_dir,
        processed_units_list,
        exclude_set_for_test,
        None,
        essential_map_paths_for_test,
        skip_if_already_loaded_for_test,
        verbose=True,
    )

    assert len(processed_units_list) == 2

    loaded_paths_from_units = {unit.relative_path for unit in processed_units_list}

    assert file1_rel_path_str in loaded_paths_from_units
    assert file3_rel_path_str in loaded_paths_from_units
    assert file2_rel_path_str not in loaded_paths_from_units
    assert file_ignored_ext_rel_path_str not in loaded_paths_from_units


@patch("scripts.llm_core.context._load_files_from_dir")
def test_prepare_context_parts_with_include_list(
    mock_load_from_dir, tmp_path: Path, monkeypatch
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)

    include_list = ["path/to/include1.txt", "path/to/include2.json"]
    for rel_path_str in include_list:
        _create_tmp_file_rel_to_project_root(
            tmp_path, rel_path_str, f"Content of {rel_path_str}"
        )

    actual_loaded_parts_genai = core_context.prepare_context_parts(
        primary_context_dir=None,
        common_context_dir=None,
        exclude_list=None,
        manifest_data=None,
        include_list=include_list,
        verbose=True,
    )

    mock_load_from_dir.assert_not_called()
    assert len(actual_loaded_parts_genai) == len(include_list)

    loaded_paths_from_genai_parts = set()
    for part in actual_loaded_parts_genai:
        match = re.search(r"--- START OF FILE (.*?) ---", part.text)
        assert match
        loaded_paths_from_genai_parts.add(match.group(1).strip())

    assert loaded_paths_from_genai_parts == set(include_list)


@pytest.mark.parametrize(
    "scenario_name, files_to_create, include_list, exclude_list, expected_loaded_paths_set, manifest_data",
    [
        (
            "include_basic",
            {"inc_file1.txt": "incl1", "other.txt": "other"},
            ["inc_file1.txt"],
            [],
            {"inc_file1.txt"},
            None,
        ),
        (
            "include_with_exclusion",
            {"inc_file1.txt": "incl1", "inc_file2.txt": "incl2_excl"},
            ["inc_file1.txt", "inc_file2.txt"],
            ["inc_file2.txt"],
            {"inc_file1.txt"},
            None,
        ),
        (
            "include_non_existent",
            {"inc_file1.txt": "incl1"},
            ["inc_file1.txt", "non_existent.txt"],
            [],
            {"inc_file1.txt"},
            None,
        ),
        (
            "include_with_manifest_summaries",
            {"inc_file1.txt": "incl1", "inc_file2.txt": "incl2"},
            ["inc_file1.txt", "inc_file2.txt"],
            [],
            {"inc_file1.txt", "inc_file2.txt"},
            {
                "files": {
                    "inc_file1.txt": {
                        "summary": "Summary for include 1",
                        "summary_token_count": 5,
                    },
                    "inc_file2.txt": {
                        "summary": "Summary for include 2",
                        "summary_token_count": 5,
                    },
                }
            },
        ),
        (
            "include_and_exclude_with_manifest",
            {
                "inc_file1.txt": "incl1",
                "inc_file2_excl.txt": "incl2_excl",
                "inc_file3.txt": "incl3_content",
            },
            ["inc_file1.txt", "inc_file2_excl.txt", "inc_file3.txt"],
            ["inc_file2_excl.txt"],
            {"inc_file1.txt", "inc_file3.txt"},
            {
                "files": {
                    "inc_file1.txt": {
                        "summary": "Summary for include 1",
                        "summary_token_count": 5,
                    },
                    "inc_file2_excl.txt": {
                        "summary": "Summary for excluded include 2",
                        "summary_token_count": 6,
                    },
                    "inc_file3.txt": {
                        "summary": "Summary for include 3",
                        "summary_token_count": 5,
                    },
                }
            },
        ),
    ],
)
def test_prepare_context_parts_with_include_list_parametrized(
    tmp_path: Path,
    monkeypatch,
    scenario_name: str,
    files_to_create: Dict[str, str],
    include_list: List[str],
    exclude_list: List[str],
    expected_loaded_paths_set: Set[str],
    manifest_data: Optional[Dict[str, Any]],
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)

    for rel_path_str, content in files_to_create.items():
        _create_tmp_file_rel_to_project_root(tmp_path, rel_path_str, content)

    actual_loaded_parts = core_context.prepare_context_parts(
        primary_context_dir=None,
        common_context_dir=None,
        exclude_list=exclude_list,
        manifest_data=manifest_data,
        include_list=include_list,
        verbose=True,
    )

    _check_loaded_parts(
        actual_loaded_parts,
        expected_loaded_paths_set,
        manifest_data,
        tmp_path,
    )


def test_get_essential_files_for_task_resolve_ac(tmp_path: Path, monkeypatch):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)
    latest_dir = "20230101_000000"
    (tmp_path / "context_llm" / "code" / latest_dir).mkdir(parents=True, exist_ok=True)
    (tmp_path / "docs").mkdir(exist_ok=True)

    # Create all files that are expected in the corrected `expected_paths_str`
    # to ensure the test logic itself can pass after correction.
    _create_tmp_file_rel_to_project_root(
        tmp_path, f"context_llm/code/{latest_dir}/github_issue_123_details.json", '{"title": "Issue 123"}'
    )
    _create_tmp_file_rel_to_project_root(
        tmp_path, "docs/guia_de_desenvolvimento.md", "Guia dev content"
    )
    _create_tmp_file_rel_to_project_root(
        tmp_path, "docs/padroes_codigo_boas_praticas.md", "Padrões content"
    )
    _create_tmp_file_rel_to_project_root(
        tmp_path, f"context_llm/code/{latest_dir}/phpunit_test_results.txt", "PHPUnit results"
    )
    _create_tmp_file_rel_to_project_root(
        tmp_path, f"context_llm/code/{latest_dir}/phpstan_analysis.txt", "PHPStan results"
    )
    _create_tmp_file_rel_to_project_root(
        tmp_path, f"context_llm/code/{latest_dir}/dusk_test_results.txt", "Dusk results"
    )
    _create_tmp_file_rel_to_project_root(
        tmp_path, "docs/descricao_evento.md", "Descrição evento content"
    )
    _create_tmp_file_rel_to_project_root(
        tmp_path, "docs/formulario_inscricao.md", "Formulário inscrição content"
    )

    args = argparse.Namespace(issue="123", ac="1") # Minimal args for the function being tested

    essential_paths_abs = core_context.get_essential_files_for_task(
        "resolve-ac", args, latest_dir, verbose=True # Keep verbose to match stdout in error log
    )
    essential_paths_relative_str = {
        p.relative_to(tmp_path).as_posix() for p in essential_paths_abs
    }

    # Corrected expected_paths_str
    expected_paths_str = {
        f"context_llm/code/{latest_dir}/github_issue_123_details.json",
        "docs/guia_de_desenvolvimento.md",
        "docs/padroes_codigo_boas_praticas.md",
        f"context_llm/code/{latest_dir}/phpunit_test_results.txt",
        f"context_llm/code/{latest_dir}/phpstan_analysis.txt",
        f"context_llm/code/{latest_dir}/dusk_test_results.txt",
        "docs/descricao_evento.md",             # Added
        "docs/formulario_inscricao.md",         # Added
        f"context_llm/code/{latest_dir}/git_diff_cached.txt",
        f"context_llm/code/{latest_dir}/git_diff_unstaged.txt",
        f"context_llm/code/{latest_dir}/project_tree_L3.txt",
    }
    assert essential_paths_relative_str == expected_paths_str



# --- Testes para load_essential_files_content (AC1.2b e AC4.1) ---
@patch("scripts.llm_core.io_utils.prompt_user_for_missing_essential_file")
def test_load_essential_files_content_ac4_1_file_missing_abort(
    mock_prompt_user: MagicMock, tmp_path: Path, monkeypatch, capsys
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)
    essential_missing_rel = "essentials/missing_file.txt"
    essential_exists_rel = "essentials/existing_file.txt"
    _create_tmp_file_rel_to_project_root(
        tmp_path, essential_exists_rel, "Content existing"
    )

    abs_paths = [tmp_path / essential_missing_rel, tmp_path / essential_exists_rel]
    mock_prompt_user.return_value = False

    with pytest.raises(MissingEssentialFileAbort) as excinfo:
        core_context.load_essential_files_content(abs_paths, 10000, verbose=True)

    assert f"ausente: {essential_missing_rel}" in str(excinfo.value)
    mock_prompt_user.assert_called_once_with(essential_missing_rel)


@patch("scripts.llm_core.io_utils.prompt_user_for_missing_essential_file")
def test_load_essential_files_content_ac4_1_file_missing_continue(
    mock_prompt_user: MagicMock, tmp_path: Path, monkeypatch, capsys
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)
    essential_missing_rel = "essentials/missing_file_continue.txt"
    essential_exists_rel = "essentials/existing_file_continue.txt"
    _create_tmp_file_rel_to_project_root(
        tmp_path, essential_exists_rel, "Content existing continue"
    )

    abs_paths = [tmp_path / essential_missing_rel, tmp_path / essential_exists_rel]
    mock_prompt_user.return_value = True

    content_str, loaded_paths = core_context.load_essential_files_content(
        abs_paths, 10000, verbose=True
    )

    assert len(loaded_paths) == 1
    assert Path(essential_exists_rel) in loaded_paths
    assert Path(essential_missing_rel) not in loaded_paths
    assert (
        core_config.ESSENTIAL_CONTENT_DELIMITER_START + essential_exists_rel
        in content_str
    )
    assert "Content existing continue" in content_str
    assert (
        core_config.ESSENTIAL_CONTENT_DELIMITER_START + essential_missing_rel
        not in content_str
    )

    mock_prompt_user.assert_called_once_with(essential_missing_rel)
    captured = capsys.readouterr()
    expected_log_message = f"AC5.1b: Continuando sem o arquivo essencial ausente '{essential_missing_rel}'."
    assert expected_log_message in captured.out


# --- Testes para prepare_payload_for_selector_llm (AC1.2d) ---
@patch(
    "scripts.llm_core.io_utils.prompt_user_for_missing_essential_file",
    return_value=True,
)
def test_prepare_payload_for_selector_llm_commit_mesage(
    mock_prompt_missing, tmp_path: Path, monkeypatch, capsys
):  # Adicionado capsys
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)
    latest_dir = "20230101_000000"
    context_code_dir = tmp_path / "context_llm" / "code" / latest_dir
    context_code_dir.mkdir(parents=True, exist_ok=True)
    (tmp_path / "docs").mkdir(exist_ok=True)

    diff_file_rel = f"context_llm/code/{latest_dir}/git_diff_cached.txt"
    log_file_rel = f"context_llm/code/{latest_dir}/git_log.txt"
    guia_file_rel = "docs/guia_de_desenvolvimento.md"

    _create_tmp_file_rel_to_project_root(tmp_path, diff_file_rel, "diff content")
    _create_tmp_file_rel_to_project_root(tmp_path, log_file_rel, "log content")
    _create_tmp_file_rel_to_project_root(tmp_path, guia_file_rel, "guia content")

    other_file_rel = "app/MyClass.php"
    _create_tmp_file_rel_to_project_root(tmp_path, other_file_rel, "class MyClass {}")

    full_manifest_data = {
        "files": {
            diff_file_rel: {
                "type": "context_code_git_diff_cached",
                "summary": "Diff summary",
                "token_count": 10,
            },
            log_file_rel: {
                "type": "context_code_git_log",
                "summary": "Log summary",
                "token_count": 15,
            },
            guia_file_rel: {
                "type": "docs_md",
                "summary": "Guia summary",
                "token_count": 20,
            },
            other_file_rel: {
                "type": "code_php",
                "summary": "MyClass summary",
                "token_count": 5,
            },
        }
    }

    selector_prompt_template = (
        "Prompt: {{ESSENTIAL_FILES_CONTENT}} \nManifesto: {{REMAINING_MANIFEST_JSON}}"
    )

    args = argparse.Namespace(issue=None)

    payload = core_context.prepare_payload_for_selector_llm(
        "commit-mesage",
        args,
        latest_dir,
        full_manifest_data,
        selector_prompt_template,
        core_config.MAX_ESSENTIAL_TOKENS_FOR_SELECTOR_CALL,
        verbose=True,
    )

    assert "diff content" in payload
    assert "log content" in payload
    assert "guia content" in payload
    assert (
        f"{core_config.ESSENTIAL_CONTENT_DELIMITER_START}{diff_file_rel} ---" in payload
    )
    assert (
        f"{core_config.ESSENTIAL_CONTENT_DELIMITER_START}{log_file_rel} ---" in payload
    )
    assert (
        f"{core_config.ESSENTIAL_CONTENT_DELIMITER_START}{guia_file_rel} ---" in payload
    )

    assert "MyClass summary" in payload
    assert diff_file_rel not in payload[payload.find("Manifesto: ") :]
    assert log_file_rel not in payload[payload.find("Manifesto: ") :]
    assert guia_file_rel not in payload[payload.find("Manifesto: ") :]

    assert "Prompt: " in payload
    assert "Manifesto: " in payload
    assert "{{ESSENTIAL_FILES_CONTENT}}" not in payload
    assert "{{REMAINING_MANIFEST_JSON}}" not in payload

    try:

        json_part_match = re.search(r"Manifesto: ({.*?})$", payload, re.DOTALL)
        assert json_part_match, "JSON part not found in payload"
        remaining_manifest_parsed = json.loads(json_part_match.group(1))
        assert "files" in remaining_manifest_parsed
        assert other_file_rel in remaining_manifest_parsed["files"]
        assert diff_file_rel not in remaining_manifest_parsed["files"]
        assert log_file_rel not in remaining_manifest_parsed["files"]
        assert guia_file_rel not in remaining_manifest_parsed["files"]
        assert (
            remaining_manifest_parsed["files"][other_file_rel]["summary"]
            == "MyClass summary"
        )
    except json.JSONDecodeError as e:
        pytest.fail(
            f"Failed to parse JSON from payload: {e}\nPayload part: {json_part_match.group(1) if json_part_match else 'Not Found'}"
        )

    captured = capsys.readouterr()
    assert (
        "AC5.1c: Tamanho total estimado do payload para LLM seletora:" in captured.out
    )


# --- Testes para AC 2.2 (Redução de Contexto) ---
@patch("scripts.llm_core.context.get_essential_files_for_task")
@patch("scripts.llm_core.api_client.calculate_max_input_tokens")
def test_ac2_2_summary_reduction(
    mock_calculate_max_tokens: MagicMock,
    mock_get_essentials: MagicMock,
    tmp_path: Path,
    monkeypatch,
    capsys,
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)

    essential_file_rel = "essencial.txt"
    non_essential_file_rel = "grande_nao_essencial.txt"

    mock_get_essentials.return_value = [tmp_path / essential_file_rel]

    _create_tmp_file_rel_to_project_root(tmp_path, essential_file_rel, "E" * (800 * 4))
    _create_tmp_file_rel_to_project_root(
        tmp_path, non_essential_file_rel, "N" * (500 * 4)
    )

    manifest_data = {
        "files": {
            essential_file_rel: {
                "summary": "Sumario do essencial",
                "token_count": 800,
                "type": "text_plain",
                "summary_token_count": 20,
            },
            non_essential_file_rel: {
                "summary": "sumario grande",
                "token_count": 500,
                "summary_token_count": 10,
                "type": "text_plain",
            },
        }
    }
    summary_grande_tokens = 10
    parts = core_context.prepare_context_parts(
        primary_context_dir=None,
        include_list=[essential_file_rel, non_essential_file_rel],
        manifest_data=manifest_data,
        max_input_tokens_for_call=1000,
        task_name_for_essentials="dummy_task_for_summary_reduction",
        cli_args_for_essentials=argparse.Namespace(),
        verbose=True,
    )

    assert len(parts) == 2

    essencial_part_text, nao_essencial_part_text = (None, None)
    for part in parts:
        if (
            core_config.ESSENTIAL_CONTENT_DELIMITER_START + essential_file_rel
            in part.text
        ):
            essencial_part_text = part.text
        elif (
            core_config.SUMMARY_CONTENT_DELIMITER_START + non_essential_file_rel
            in part.text
        ):
            nao_essencial_part_text = part.text
    assert essencial_part_text is not None and nao_essencial_part_text is not None
    assert "E" * (800 * 4) in essencial_part_text
    assert "sumario grande" in nao_essencial_part_text
    assert "N" * (500 * 4) not in nao_essencial_part_text
    captured = capsys.readouterr()
    expected_log_non_essential_reduction = f"AC2.2.1: Substituindo '{non_essential_file_rel}' ({500} tokens originais) por sumário ({summary_grande_tokens} tokens)."
    assert expected_log_non_essential_reduction in captured.out
    assert f"AC2.2.1: Substituindo '{essential_file_rel}'" not in captured.out


@patch("scripts.llm_core.context.get_essential_files_for_task")
@patch("scripts.llm_core.api_client.calculate_max_input_tokens")
def test_ac2_2_truncation(
    mock_calculate_max_tokens: MagicMock,
    mock_get_essentials: MagicMock,
    tmp_path: Path,
    monkeypatch,
    capsys,
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)

    mock_get_essentials.return_value = []
    original_content_1000_tokens = "X" * (1000 * 4)
    _create_tmp_file_rel_to_project_root(
        tmp_path, "muito_grande.txt", original_content_1000_tokens
    )
    manifest_data = {
        "files": {
            "muito_grande.txt": {
                "summary": None,
                "token_count": 1000,
                "type": "text_plain",
                "summary_token_count": None,
            }
        }
    }
    parts = core_context.prepare_context_parts(
        primary_context_dir=None,
        include_list=["muito_grande.txt"],
        manifest_data=manifest_data,
        max_input_tokens_for_call=500,
        task_name_for_essentials=None,
        cli_args_for_essentials=argparse.Namespace(),
        verbose=True,
    )

    assert len(parts) == 1
    part_text = parts[0].text

    assert "... [CONTEÚDO TRUNCADO PARA CABER NO LIMITE DE TOKENS] ..." in part_text

    assert original_content_1000_tokens not in part_text
    assert (
        len(part_text)
        < len(original_content_1000_tokens)
        + len(core_config.SUMMARY_CONTENT_DELIMITER_START)
        + len("muito_grande.txt ---")
        + len("--- SUMMARY --- ... --- END SUMMARY ---")
        + len(core_config.SUMMARY_CONTENT_DELIMITER_END)
        + len("muito_grande.txt ---")
        + 200
    )
    captured = capsys.readouterr()

    assert "AC2.2.2 (Não Essencial): Truncando 'muito_grande.txt'" in captured.out

    match_tokens = re.search(
        r"Truncando 'muito_grande.txt' de 1000 para (\d+) tokens", captured.out
    )
    assert match_tokens, "Log de truncamento não encontrado ou formato inesperado"
    final_tokens = int(match_tokens.group(1))
    assert final_tokens <= 500
    assert final_tokens > 0


@patch(
    "scripts.llm_core.io_utils.prompt_user_for_missing_essential_file",
    return_value=True,
)
def test_ac3_4_essential_file_truncation_and_logging_for_selector_llm(
    mock_prompt_missing, tmp_path: Path, monkeypatch, capsys
):
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)
    essential_large_rel = "essentials/grande_essencial.txt"
    essential_small_rel = "essentials/pequeno_essencial.txt"
    _create_tmp_file_rel_to_project_root(tmp_path, essential_large_rel, "L" * 4000)
    _create_tmp_file_rel_to_project_root(tmp_path, essential_small_rel, "S" * 400)
    abs_paths = [tmp_path / essential_large_rel, tmp_path / essential_small_rel]
    max_tokens_payload = 700
    content_str, loaded_paths = core_context.load_essential_files_content(
        abs_paths, max_tokens_payload, verbose=True
    )
    assert Path(essential_large_rel) in loaded_paths
    assert Path(essential_small_rel) not in loaded_paths
    assert "... [CONTEÚDO TRUNCADO PARA CABER NO LIMITE DE TOKENS] ..." in content_str
    captured = capsys.readouterr()
    expected_log_decision_to_truncate_large = f"Conteúdo do arquivo essencial '{essential_large_rel}' ({int(4000/3.8)} tokens est.) será truncado para caber no orçamento de {max_tokens_payload} tokens."
    assert (
        expected_log_decision_to_truncate_large in captured.out
    ), "Log de decisão de truncamento para arquivo essencial grande não encontrado ou formato incorreto."
    assert "Conteúdo truncado:" in captured.out

    # CORREÇÃO: Verifica a mensagem genérica de skip de arquivos restantes
    expected_log_overall_skip = f"Limite de tokens para payload essencial ({max_tokens_payload}) atingido. Pulando arquivos restantes."
    assert (
        expected_log_overall_skip in captured.out
    ), f"Log esperado de skip geral não encontrado na saída: {captured.out}"

    max_tokens_tiny_payload = 10
    content_str_tiny, loaded_paths_tiny = core_context.load_essential_files_content(
        [tmp_path / essential_large_rel], max_tokens_tiny_payload, verbose=True
    )
    captured_tiny = capsys.readouterr()
    expected_log_tiny_skip = f"Arquivo essencial '{essential_large_rel}' ({int(4000/3.8)} tokens est.) pulado. Orçamento restante ({max_tokens_tiny_payload} tokens) muito pequeno para conteúdo útil."
    assert expected_log_tiny_skip in captured_tiny.out


@patch("builtins.input")
def test_confirm_and_modify_selection_logs(mock_input, capsys, tmp_path: Path):
    monkeypatch = pytest.MonkeyPatch()
    monkeypatch.setattr(core_config, "PROJECT_ROOT", tmp_path)

    suggested = ["fileA.py", "fileB.md"]

    (tmp_path / "fileA.py").write_text("content A")
    (tmp_path / "fileB.md").write_text("content B")
    (tmp_path / "fileC.js").write_text("content C")

    manifest = {
        "files": {
            "fileA.py": {"token_count": 100, "type": "code_python"},
            "fileB.md": {"token_count": 200, "type": "docs_md"},
            "fileC.js": {"token_count": 50, "type": "code_js"},
        }
    }

    mock_input.side_effect = ["a fileC.js", "r fileA.py", "y"]

    final_selection = core_context.confirm_and_modify_selection(
        suggested, manifest, max_input_tokens=500, verbose=True
    )

    captured = capsys.readouterr()

    # AC5.1d: Lista inicial
    assert (
        "AC5.1d: Lista de arquivos retornada pela LLM seletora (2 arquivos):"
        in captured.out
    )
    assert "    [1] fileA.py" in captured.out  # CORREÇÃO
    assert "    [2] fileB.md" in captured.out  # CORREÇÃO

    # Log de adição (AC5.1e)
    assert "AC5.1e: Usuário adicionou 'fileC.js' à seleção." in captured.out

    # Log de remoção (AC5.1e)
    assert "AC5.1e: Usuário removeu 'fileA.py' da seleção." in captured.out

    # Verifica o log final de confirmação
    assert (
        "Usuário confirmou o uso de 2 arquivos selecionados para o contexto."
        in captured.out
    )
    assert final_selection == ["fileB.md", "fileC.js"]

    monkeypatch.undo()
