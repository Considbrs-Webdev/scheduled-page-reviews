<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * Where the resolved value for a single field came from.
 *
 *  - GlobalDefault:   no rule anywhere; value comes from {@see GlobalSettings}.
 *  - Inherited:       set by an ancestor with subtree scope.
 *  - Local:           set on this page itself with self scope.
 *  - LocalPropagated: set on this page itself with subtree scope (will be
 *                     the new inherited value for descendants).
 *
 * The string values are stable and used by the frontend to render the
 * inheritance badges.
 */
enum FieldSource: string
{
    case GlobalDefault   = 'default';
    case Inherited       = 'inherited';
    case Local           = 'local';
    case LocalPropagated = 'local-propagated';
}
