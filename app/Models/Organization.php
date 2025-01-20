<?php

namespace App\Models;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class Organization extends Model
{
    protected $fillable = ['org_name'];

    /**
     * A method that allows you to get all the daughters of a company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function daughters()
    {
        return $this->belongsToMany(Organization::class, 'relationships', 'organization_id', 'daughter_id');
    }

    /**
     * A method that allows you to get all the parents of a company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function parents()
    {
        return $this->belongsToMany(Organization::class, 'relationships', 'daughter_id', 'organization_id');
    }

    /**
     * A method that allows you to get all the parents, daughters and sisters of a company
     *
     * @param $organizationName
     * @return ResponseFactory|Application|JsonResponse|Response
     */
    public static function organizationRelationships($organizationName)
    {
        if(!Organization::where('org_name', $organizationName)->exists()) return response(null, 404);

        // Finds the required organization in the database by the organization name
        $organization = self::where('org_name', $organizationName)->first();

        $relationshipsList = [];

        // Adds all parents and daughters of the organization to the list
        foreach ($organization->parents as $parents) {
            $relationship = [
                'relationship_type' => 'parent',
                'org_name' => $parents->org_name
            ];

            // If the organization is not yet on the list, it is added to the list
            if(!in_array($relationship, $relationshipsList)) {
                $relationshipsList[] = $relationship;
            }

            // Adds all sisters of the organization to the list
            foreach ($parents->daughters as $sisters) {

                // If it is the same organization that is being searched for, then it is skipped
                if($sisters->org_name == $organization->org_name) continue;

                $relationship = [
                    'relationship_type' => 'sister',
                    'org_name' => $sisters->org_name
                ];

                // If the organization is not yet on the list, it is added to the list
                if(!in_array($relationship, $relationshipsList)) {
                    $relationshipsList[] = $relationship;
                }
            }
        }

        // Adds all children of the organization to the list
        foreach ($organization->daughters as $daughter) {
            $relationship = [
                'relationship_type' => 'daughter',
                'org_name' => $daughter->org_name
            ];

            // If the organization is not yet on the list, it is added to the list
            if(!in_array($relationship, $relationshipsList)) {
                $relationshipsList[] = $relationship;
            }
        }

        // Using a helper method, sorts all organizations into a list by name
        $relationshipsList = self::array_sort($relationshipsList, 'org_name');

        // Gets the current pagination page
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // Splits a list into collections
        $collection = collect($relationshipsList);

        // Specifies the required number of elements on the page.
        // Splits the collection into the required number of elements.
        $perPage = 100;
        $currentPageResults = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        // Returns the desired page with all the information.
        return response()->json(new LengthAwarePaginator(
            $currentPageResults,
            count($collection),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        ));
    }

    /**
     * The method creates a tree of organizations
     *
     * @param $organization
     * @return array
     */
    public static function getAllOrganizations($organization = null)
    {
        $tree = [];

        // Finds all root organizations (which have no parents) and starts building a tree from them
        if($organization == null) {

            // Get all parents of the organization
            $rootOrganizations = Organization::whereDoesntHave('parents')->get();

            // Build a tree for each organization
            foreach ($rootOrganizations as $rootOrganization) {
                $tree = Organization::getAllOrganizations($rootOrganization);
            }

            return $tree;
        }

        // Finds all the daughters of the organization and continues to build the tree
        $children = $organization->daughters->map(function ($child) {
            return self::getAllOrganizations($child);
        });

        // If this is the last element of the tree, adds it to the list
        if($children->count() == 0) {
            return [
                'org_name' => $organization->org_name
            ];
        }

        // Returns an organization with a complete list of its daughters
        return [
            'org_name' => $organization->org_name,
            'daughters' => $children,
        ];
    }

    /**
     * Recursively adds all organizations and connections to them from json
     *
     * @return mixed
     */
    public static function createOrganizations($data, $parentId = null)
    {
        // If the organization does not exist in the database, it is added there.
        // If an organization with the same name already exists, it is found from the database.
        if (!self::where('org_name', $data['org_name'])->exists()) {
            $organization = self::create(['org_name' => $data['org_name']]);
        }
        else {
            $organization = self::where('org_name', $data['org_name'])->first();
        }

        // If the organization has a parent, adds a relationship between the parent and the organization
        if ($parentId)
        {
            // Checks to avoid creating two identical relationships between organizations,
            // and does not add a relationship if it already exists in reverse order.
            if(!Relationship::where('organization_id', $parentId)->where('daughter_id', $organization->id)->exists() &&
                !Relationship::where('organization_id', $organization->id)->where('daughter_id', $parentId)->exists())
            {
                $parent = self::find($parentId);

                // Adds a connection between organizations
                $parent->daughters->attach($organization->id);
            }
        }

        // Checks that the variable is not null and also checks that the list is not empty and continues the recursion
        if (isset($data['daughters']) && is_array($data['daughters']))
        {
            foreach ($data['daughters'] as $daughter)
            {
                self::createOrganizations($daughter, $organization->id);
            }
        }

        return $organization;
    }

    /**
     * Helper method for sorting a list with lists inside
     *
     * @param $array
     * @param $on
     * @param $order
     * @return array
     */
    private static function array_sort($array, $on, $order=SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                array_push($new_array, $array[$k]);
            }
        }

        return $new_array;
    }
}
