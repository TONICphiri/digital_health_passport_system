<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Thrown when a user action breaks a business rule, for example allocating
 * a passport that is not open. The message is written for the user and is
 * shown on the previous page, with the submitted form values kept.
 */
class WorkflowException extends RuntimeException
{
    public function render(): RedirectResponse
    {
        return back()->withInput()->with('error', $this->getMessage());
    }
}
