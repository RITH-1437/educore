<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised by services when a domain rule blocks an otherwise valid request
 * (illegal status transition, deleting a parent that still has children...).
 *
 * Mapped to `409 Conflict` in `bootstrap/app.php` so controllers never build
 * ad-hoc error payloads — see `skills/api/SKILL.md`.
 */
class BusinessRuleException extends RuntimeException {}
