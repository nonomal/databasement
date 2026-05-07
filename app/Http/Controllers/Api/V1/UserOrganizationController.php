<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags User
 */
class UserOrganizationController extends Controller
{
    /**
     * List the authenticated user's organizations.
     *
     * Returns all organizations the current user belongs to, including their
     * role in each. Use the `id` from the response as the `org_id` query
     * parameter or `X-Organization-Id` header to scope requests to a
     * specific organization.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return OrganizationResource::collection(
            $user->organizations()->orderByPivot('created_at')->get()
        );
    }
}
