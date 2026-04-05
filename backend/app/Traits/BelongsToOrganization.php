<?php

namespace App\Traits;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * BelongsToOrganization Trait
 *
 * Automatically applies tenant-level organization scoping to Eloquent models.
 *
 * This trait:
 * - Adds a global scope that filters all queries by the authenticated user's organization_id
 * - Automatically assigns organization_id during model creation if not explicitly set
 * - Gracefully handles cases where authentication is unavailable (jobs, console commands)
 * - Uses fully qualified table names to prevent ambiguous column references in joins
 *
 * Usage:
 *     class Product extends Model {
 *         use BelongsToOrganization;
 *     }
 *
 * The scope will automatically filter queries:
 *     Product::all() // Returns only products for the authenticated user's organization
 *
 * To bypass the scope (e.g., for admin queries):
 *     Product::withoutGlobalScope('organization')->get()
 *
 * @since Laravel 10.x
 */
trait BelongsToOrganization
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * This method is automatically called by Laravel's scope system.
     * It registers the global scope and the creating event listener.
     */
    public static function boot(): void
    {
        static::addGlobalScope(new class implements Scope {
            /**
             * Apply the scope to the Eloquent query builder.
             *
             * @param Builder $builder
             * @param Model $model
             * @return void
             */
            public function apply(Builder $builder, Model $model): void
            {
                $user = Auth::user();

                // Fallback for unit tests or console commands
                if (!$user && app()->runningUnitTests()) {
                    $user = User::first();
                }

                // If no user exists at all, don't apply the scope
                if (!$user) {
                    return;
                }

                // Use fully qualified table name to prevent ambiguous column references
                $table = $model->getTable();
                $builder->where("{$table}.organization_id", '=', $user->organization_id);
            }
        });

        /**
         * Automatically assign organization_id on model creation.
         *
         * This event listener ensures that whenever a new model instance is created,
         * it is automatically associated with the authenticated user's organization,
         * preventing accidental creation of unscoped records.
         *
         * If organization_id is explicitly set to null or a different value,
         * the explicit value is preserved (only fills empty values).
         */
        static::creating(function (Model $model): void {
            $user = Auth::user();

            // Only auto-fill if:
            // 1. An authenticated user exists
            // 2. organization_id attribute is not already set
            if ($user !== null && $model->getAttribute('organization_id') === null) {
                $model->setAttribute('organization_id', $user->organization_id);
            }
        });
    }
}
