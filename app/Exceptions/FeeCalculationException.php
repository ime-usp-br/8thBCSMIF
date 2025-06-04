<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for errors encountered during fee calculation.
 * This exception will be used by FeeCalculationService to indicate issues
 * such as missing fee configurations or other calculation problems.
 */
class FeeCalculationException extends Exception
{
    // Future ACs might add specific properties or methods here if needed.
}
