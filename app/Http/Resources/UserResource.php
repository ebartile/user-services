<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'presence'          => $this->presence,
            'profile'           => UserProfileResource::make($this->whenLoaded('profile')),
            'last_seen_at'      => $this->last_seen_at,
            'last_login_at'     => $this->last_login_at,
            'active'            => $this->active,
            'long_term'         => $this->long_term,
            'email_verified_at' => $this->email_verified_at,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,

            $this->mergeWhen($request->user()?->canViewUser($this->resource), [
                'email'                 => $this->email,
                'deactivated_until'     => $this->deactivated_until
            ]),

            'updatable' => $request->user()->canUpdateUser($this->resource),
            'deletable' => $request->user()->canDeleteUser($this->resource),
        ];
    }
}
