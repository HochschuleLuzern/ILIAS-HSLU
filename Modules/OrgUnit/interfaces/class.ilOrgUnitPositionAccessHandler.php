<?php

/**
 * Interface  ilOrgUnitPositionAccessHandler
 *
 * Provides access checks due to a users OrgUnit-Positions
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
interface ilOrgUnitPositionAccessHandler {

	const PERMISSION_VIEW_LEARNING_PROGRESS = 'viewlp';
	const PERMISSION_VIEW_TEST_RESULTS = 'viewtstr';


	/**
	 * @param int[] $user_ids List of ILIAS-User-IDs which shall be filtered
	 *
	 * @return int[] Filtered List of ILIAS-User-IDs
	 */
	public function filterUserIdsForCurrentUsersPositionsAndAnyPermission(array $user_ids);


	/**
	 * @param int[] $user_ids    List of ILIAS-User-IDs which shall be filtered
	 *
	 * @param int   $for_user_id ID od the user, for which
	 *
	 * @return int[] Filtered List of ILIAS-User-IDs
	 */
	public function filterUserIdsForUsersPositionsAndAnyPermission(array $user_ids, $for_user_id);


	/**
	 * @param int[]  $user_ids           List of ILIAS-User-IDs which shall be filtered
	 *
	 * @param string $permission         See the list of
	 *                                   available permissions in interface
	 *                                   ilOrgUnitPositionAccessHandler
	 *
	 * @throws \ilOrgUnitAccessException when a unknown permission is used. See the list of
	 *                                   available permissions in interface
	 *                                   ilOrgUnitPositionAccessHandler
	 *
	 *
	 * @return int[] Filtered List of ILIAS-User-IDs
	 */
	public function filterUserIdsForCurrentUsersPositionsAndPermission(array $user_ids, $permission);


	/**
	 * @param int[]  $user_ids           List of ILIAS-User-IDs which shall be filtered
	 * @param int    $for_user_id
	 * @param string $permission         See the list of
	 *                                   available permissions in interface
	 *                                   ilOrgUnitPositionAccessHandler
	 *
	 * @throws \ilOrgUnitAccessException when a unknown permission is used. See the list of
	 *                                   available permissions in interface
	 *                                   ilOrgUnitPositionAccessHandler
	 *
	 * @return int[] Filtered List of ILIAS-User-IDs
	 */
	public function filterUserIdsForUsersPositionsAndPermission(array $user_ids, $for_user_id, $permission);


	/**
	 * @param string $permission         See the list of
	 *                                   available permissions in interface
	 *                                   ilOrgUnitPositionAccessHandler
	 * @param int[]  $on_user_ids        List of ILIAS-User-IDs
	 *
	 * @return bool
	 */
	public function isCurrentUserBasedOnPositionsAllowedTo($permission, array $on_user_ids);


	/**
	 * @param int    $which_user_id      Permission check for this ILIAS-User-ID
	 *
	 * @param string $permission         See the list of
	 *                                   available permissions in interface
	 *                                   ilOrgUnitPositionAccessHandler
	 * @param int[]  $on_user_ids        List of ILIAS-User-IDs
	 *
	 * @return bool
	 */
	public function isUserBasedOnPositionsAllowedTo($which_user_id, $permission, array $on_user_ids);


	/**
	 * @param string $pos_perm           See the list of
	 *                                   available permissions in interface
	 *                                   ilOrgUnitPositionAccessHandler
	 * @param int    $ref_id             Reference-ID of the desired Object in the tree
	 *
	 * @return bool
	 */
	public function checkPositionAccess($pos_perm, $ref_id);


	/**
	 * @param string $pos_perm
	 * @param int    $ref_id
	 * @param int[]  $user_ids
	 *
	 * @return int[]
	 */
	public function filterUserIdsByPositionOfCurrentUser($pos_perm, $ref_id, array $user_ids);


	/**
	 * @param int    $user_id
	 * @param string $pos_perm
	 * @param int    $ref_id
	 * @param int[]  $user_ids
	 *
	 * @return int[]
	 */
	public function filterUserIdsByPositionOfUser($user_id, $pos_perm, $ref_id, array $user_ids);


}
