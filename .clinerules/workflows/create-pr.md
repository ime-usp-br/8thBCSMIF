---
description: "A comprehensive workflow for creating pull requests with GitHub CLI, including branch analysis, security checks, and automated PR content generation following best practices."
---

## Guide: Intelligent and Secure Pull Request Creation

This workflow automates the entire pull request creation process: it analyzes the current branch changes, performs security validation, gathers context from commit history and existing issues, and generates a standardized PR with proper title, description, and labels.

### 1. Branch and Repository Status Analysis
The AI assistant will first verify the current branch state and ensure it's ready for a pull request.

<execute_command>
<command>git status | cat</command>
<# Verifies working tree is clean and branch is ready for PR creation. #>
<requires_approval>false</requires_approval>
</execute_command>

<execute_command>
<command>git branch --show-current</command>
<# Gets the current branch name to ensure we're not creating a PR from main/master. #>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Ensure Branch is Pushed to Remote
Before creating a PR, we need to ensure the branch exists on the remote repository.

<execute_command>
<command>git ls-remote --exit-code --heads origin $(git branch --show-current)</command>
<# Checks if the current branch exists on the remote. If it fails, we'll need to push first. #>
<requires_approval>false</requires_approval>
</execute_command>

**Instructions for the AI Assistant:**
*   If the command above **failed** (non-zero exit code), the branch doesn't exist remotely. **Push the branch first** with `git push --set-upstream origin $(git branch --show-current)`.
*   If the command **succeeded** (exit code 0), proceed to the next step.

### 3. Gather Changes for Analysis
The AI assistant will collect all changes that will be included in the pull request for comprehensive analysis.

**A. Get Commit History for the Branch:**

<execute_command>
<command>git log origin/main..HEAD --pretty=format:"%C(yellow)%h %C(reset)- %s %C(green)(%cr) %C(bold blue)<%an>%C(reset)" | cat</command>
<# Lists all commits that will be included in the PR, showing the scope of changes. #>
<requires_approval>false</requires_approval>
</execute_command>

**B. Get Complete Diff for Security Analysis:**

<execute_command>
<command>git diff origin/main..HEAD | cat</command>
<# Provides the complete diff for the AI assistant to analyze for security issues and understand the changes. #>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Critical Security Analysis (Performed by the AI Assistant)
This is a mandatory security checkpoint. The AI assistant will analyze all changes in the pull request for sensitive data.

**Instructions for the AI Assistant (Mandatory Action):**
1.  **Analyze the complete diff from the previous command.**
2.  **Actively search for:**
    *   **High-risk keywords:** `password`, `secret`, `key`, `token`, `bearer`, `private`, `credential`, `.env`.
    *   **API key patterns:** (e.g., `sk_live_`, `pk_live_`, `ghp_`, long strings with high entropy).
    *   **Hardcoded credentials:** URLs with embedded credentials, database connection strings.
    *   **Suspicious comments:** (e.g., `// TODO: remove password before merging`).
3.  **IF ANYTHING SUSPICIOUS IS FOUND:**
    *   **Stop the process immediately.**
    *   **Clearly inform the user, showing the problematic code snippet.**
    *   **Ask explicitly:** "I have detected what appears to be sensitive data in the changes. Do you wish to proceed with the PR creation anyway? (yes/no)"
    *   If the answer is "no", **terminate the workflow immediately** and instruct the user to fix the issue.
4.  **IF NOTHING IS FOUND:**
    *   Inform the user: "✅ Security scan complete. No apparent secrets were found." and proceed to the next step.

### 5. Context Gathering for PR Content Generation
To create the best possible PR title and description, the AI assistant will analyze project patterns and open issues.

**A. Recent PR History for Style Patterns:**

<execute_command>
<command>gh pr list --state merged --limit 10 --json number,title,body | cat</command>
<# Analyzes recent PR titles and descriptions to learn the project's style and conventions. #>
<requires_approval>false</requires_approval>
</execute_command>

**B. Open GitHub Issues for Task Connection:**

<execute_command>
<command>gh issue list --state open --json number,title,labels,body | cat</command>
<# The AI assistant will analyze this to connect the PR to existing issues and understand the context. #>
<requires_approval>false</requires_approval>
</execute_command>

**C. Repository Information for Labels and Assignees:**

<execute_command>
<command>gh repo view --json owner,name,defaultBranch</command>
<# Gets repository metadata for proper PR configuration. #>
<requires_approval>false</requires_approval>
</execute_command>

### 6. PR Content Generation (AI Assistant's Action)
With all context gathered, the AI assistant will construct an optimal pull request with proper title, description, and metadata.

**Instructions for the AI Assistant:**
1.  **Analyze the diff and commits** to understand the scope and purpose of changes.
2.  **Use the PR history** to determine the correct title format and description style.
3.  **Cross-reference with open issues** to identify if this PR addresses any existing issues.
4.  **Generate a clear, descriptive title** that follows project conventions.
5.  **Create a comprehensive description** including:
    *   **## Summary:** Brief overview of the changes
    *   **## Changes Made:** Bullet points of specific modifications
    *   **## Related Issues:** Reference relevant issues (use `Closes #number` if appropriate)
    *   **## Testing:** Description of how changes were tested
    *   **## Screenshots/Demo:** If applicable (UI changes)
6.  **Suggest appropriate labels** based on the type of changes (bug, feature, documentation, etc.).
7.  **CRITICAL:** Do not include any references to AI assistants, LLMs, or specific tools in the PR title or description.

### 7. Pull Request Creation (with User Approval)
The AI assistant will generate the complete `gh pr create` command with all necessary flags and content.

<execute_command>
<command>
# The AI assistant will generate the complete gh pr create command here.
# Example format:
# gh pr create \
#   --title "feat: Add user authentication system" \
#   --body "$(cat <<'EOF'
# ## Summary
# Implements comprehensive user authentication with JWT tokens and role-based access control.
#
# ## Changes Made
# - Add authentication middleware for Express routes
# - Implement JWT token generation and validation
# - Create user registration and login endpoints
# - Add password hashing with bcrypt
# - Implement role-based authorization
#
# ## Related Issues
# Closes #42
# Closes #38
#
# ## Testing
# - Unit tests for authentication middleware
# - Integration tests for auth endpoints
# - Manual testing of login/logout flow
# EOF
# )" \
#   --label "feature" \
#   --label "backend" \
#   --assignee "@me"
</command>
<# Review the PR title, description, and metadata generated by the AI assistant. If correct, approve to create the PR. #>
<requires_approval>true</requires_approval>
</execute_command>

### 8. Post-Creation Verification
After the PR is created, verify it was successful and provide the PR URL for easy access.

<execute_command>
<command>gh pr view --json number,title,url | cat</command>
<# Displays the created PR information including the URL for immediate access. #>
<requires_approval>false</requires_approval>
</execute_command>

**Final Notes:**
- The AI assistant will provide the direct PR URL for immediate review
- Consider enabling auto-merge if the repository supports it and all checks pass
- The PR will be ready for review by team members