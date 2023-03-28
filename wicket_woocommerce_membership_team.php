<?php

/*
Plugin Name: Wicket WooCommerce Membership Team
Description: wicket.io extensions required for WooCommerce integrations. Add webhook support for Membership Team
Author: Wicket
*/

add_filter( 'register_post_type_args', 'show_post_types_in_rest', 10, 2 );
add_action('rest_api_init', 'show_meta_fields_in_rest');

function show_post_types_in_rest( $args, $post_type ) {
 
  if ( 'wc_memberships_team' === $post_type ) {
        $args['public'] = true;
        $args['show_in_rest'] = true;
        $args['supports'] = array( 'title', 'custom-fields' );
        $args['hierarchical'] = true;
    }

    return $args;
}

function show_meta_fields_in_rest() {

    register_meta( 'post', 'wicket_organization', array(
        'type' => 'string',
        'subtype' => 'wc_memberships_team',
        'single' => true,
        'show_in_rest' => true
    ));
    register_meta( 'post', '_seat_count', array(
        'type' => 'string',
        'subtype' => 'wc_memberships_team',
          'single' => true,
        'show_in_rest' => true
    ));
    register_meta( 'post', '_member_id', array(
        'type' => 'string',
        'subtype' => 'wc_memberships_team',
        'single' => false,
        'show_in_rest' => true
    ));
    register_meta( 'post', '_edit_last', array(
        'type' => 'string',
        'subtype' => 'wc_memberships_team',
        'single' => true,
        'show_in_rest' => true
    ));
    register_meta( 'post', '_start_date', array(
        'type' => 'string',
        'subtype' => 'wc_memberships_team',
        'single' => true,
        'show_in_rest' => true
    ));
    register_meta( 'post', '_end_date', array(
        'type' => 'string',
        'subtype' => 'wc_memberships_team',
        'single' => true,
        'show_in_rest' => true
    ));
}

/** Keeps track of webhook sent to avoid duplicates */
$sent_webhooks = [];

// add webhook resources and events
add_filter( 'woocommerce_valid_webhook_resources', 'add_resources' );
add_filter( 'woocommerce_valid_webhook_events', 'add_events' );

// add webhook topics and their hooks
add_filter( 'woocommerce_webhook_topics', 'add_topics' );
add_filter( 'woocommerce_webhook_topic_hooks', 'add_topic_hooks', 10, 2 );

// create webhook payloads
add_filter( 'woocommerce_webhook_payload', 'create_payload', 1, 4 );

// check whether webhook should be delivered
add_filter( 'woocommerce_webhook_should_deliver', 'handle_webhook_delivery', 100, 3 );

// whitelist custom fields
add_filter( 'wc_memberships_for_teams_allowed_meta_box_ids', 'allow_custom_field_metaboxes' );

// when creating a membership team from admin, look for posts going from auto draft to publish status
add_action( 'transition_post_status', 'handle_new_object_published', 10, 3 );

// add actions for membership team webhooks consumption
add_action( 'wp_insert_post', 'add_membership_team_created_webhook_action', 999, 3 );
add_action( 'wp_insert_post', 'add_membership_team_updated_webhook_action', 999, 3 );
add_action( 'post_updated',   'add_membership_team_updated_webhook_action', 999, 2 );
add_action( 'trashed_post',   'add_membership_team_deleted_webhook_action', 999 );
add_action( 'untrashed_post', 'add_membership_team_restored_webhook_action', 999 );

// when adding a member, add wicket_organization as metadata
add_action( 'wc_memberships_for_teams_add_team_member', 'add_wicket_organization_metadata', 10 , 3 );

// when saving a user membership, add username as metadata
add_action( 'wc_memberships_user_membership_saved', 'add_username_metadata', 10 , 2 );

// when transferring a user membership, update username metadata
add_action( 'wc_memberships_user_membership_transferred', 'update_username_when_transfer', 10 , 2 );

/**
 * Adds team objects to webhook resources.
 *
 * @internal
 *
 * @param string[] $resources array of resources
 * @return string[]
 */
function add_resources( array $resources ) {

    $resources[] = 'membership_team';

    return array_unique( $resources );
}


/**
 * Adds teams events to webhook events.
 *
 * @internal
 *
 * @param string[] $events array of events
 * @return string[]
 */
function add_events( array $events ) {

    $teams_events = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    foreach ( $teams_events as $teams_event ) {
        $events[] = $teams_event;
    }

    return array_unique( $events );
}


/**
 * Adds topics to the webhooks topic selection dropdown.
 *
 * This is typically within the admin webhook edit screens.
 *
 * @internal
 *
 * @param array $topics associative array
 * @return array
 */
function add_topics( array $topics ) {

    $membership_team_topics = [
        'membership_team.created' => __( 'Membership Team Created', 'woocommerce-memberships-teams' ),
        'membership_team.updated' => __( 'Membership Team Updated', 'woocommerce-memberships-teams' ),
        'membership_team.deleted' => __( 'Membership Team Deleted', 'woocommerce-memberships-teams' ),
        'membership_team.restored' => __( 'Membership Team Restored', 'woocommerce-memberships-teams' ),
    ];

    return array_merge( $topics, $membership_team_topics );
}


/**
 * Adds hooks to webhook topics.
 *
 * @internal
 *
 * @param array $topic_hooks topic hooks associative array
 * @param \WC_Webhook $webhook webhook object
 * @return array
 */
function add_topic_hooks( $topic_hooks, $webhook ) {

    $resource = $webhook->get_resource();

    if ( 'membership_team' === $resource ) {

        /**
         * Filters the membership teams webhook topics.
         *
         * @param array $topic_hooks associative array of topics
         * @param \WC_Webhook $webhook webhook object
         */
        $topic_hooks = (array) apply_filters( 'wc_memberships_membership_team_webhook_topic_hooks', [
            'membership_team.created'  => [
                'wc_memberships_webhook_membership_team_created',
            ],
            'membership_team.updated'  => [
                'wc_memberships_webhook_membership_team_updated',
            ],
            'membership_team.deleted'  => [
                'wc_memberships_webhook_membership_team_deleted',
            ],
            'membership_team.restored' => [
                'wc_memberships_webhook_membership_team_restored',
            ],
        ], $webhook );
    }

    return $topic_hooks;
}


/**
 * Creates a payload for membership teams webhook deliveries.
 *
 * @internal
 *
 * @param array|\WP_REST_Response $payload payload data
 * @param string $resource resource to be handled
 * @param int $resource_id resource ID
 * @param int $webhook_id webhook ID
 * @return array|\WP_REST_Response
 */
function create_payload( $payload, $resource, $resource_id, $webhook_id ) {

    if ( empty( $payload ) ) {

            if ( 'membership_team' === $resource ) {
                $payload = get_payload( $resource_id, $webhook_id );
            }
    }

    return $payload;
}


/**
 * Gets a webhook payload for a membership team object.
 *
 * @param int $resource_id membership team object ID
 * @param int $webhook_id WooCommerce webhook ID
 * @return array|\WP_REST_Response
 */
function get_payload( $resource_id, $webhook_id ) {

    $payload = [];

    try {

        $webhook  = new \WC_Webhook( $webhook_id );
        $old_user = get_current_user_id();

        wp_set_current_user( $webhook->get_user_id() );

        if ( 'deleted' === $webhook->get_event() || ! get_post( $resource_id ) ) {
            $payload = [ 'id' => (int) $resource_id ];
        } else {
            $team = wc_memberships_for_teams_get_team( $resource_id );
            $payload = get_formatted_item_data( $team );
        }

        wp_set_current_user( $old_user );

    } catch( \Exception $e ) {}

    return $payload;
}


/**
 * Validates whether a webhook should deliver its payload.
 *
 * Ensures an empty payload is not sent, unless the event is for deleted data.
 *
 * @internal
 *
 * @param bool $deliver_payload whether webhook should delivery payload
 * @param \WC_Webhook $webhook webhook object
 * @param int $resource_id membership team object ID
 * @return bool
 */
function handle_webhook_delivery( $deliver_payload, $webhook, $resource_id ) {

    $resource = $webhook->get_resource();

    if ( 'membership_team' == $resource ) {

        if ( 'deleted' === $webhook->get_event() ) {

            $deliver_payload = true;

        } elseif ( $deliver_payload ) {

            $deliver_payload = wc_memberships_for_teams_get_team( $resource_id );

        }
    }

    return $deliver_payload;
}


/**
 * Handles membership team creation from admin, where the post may have an auto draft status initially.
 *
 * @internal
 *
 * @param string  $new_status new status assigned to the post
 * @param string $old_status old status the post is moving away from
 * @param \WP_Post $post_object a WordPress post that could be of a membership team
 */
function handle_new_object_published( $new_status, $old_status, $post_object ) {

    if ( in_array( $old_status, [ 'auto-draft', 'new' ], true ) ) {

        $post_type = get_post_type( $post_object );

        if ( 'wc_memberships_team' === $post_type && 'publish' === $new_status ) {
            add_membership_team_created_webhook_action( $post_object->ID, $post_object, false );
        }
    }
}

/**
 * Adds wicket_organization metadata to the membership.
 *
 * @internal
 *
 * @param Team_Member $member the team member instance
 * @param Team $team the team instance
 * @param \WC_Memberships_User_Membership $user_membership the related user membership instance
 */
function add_wicket_organization_metadata($member, $team, $user_membership) {
    update_post_meta( $user_membership->get_id(), 'wicket_organization', get_field( "wicket_organization", $team->get_id() ) );
}

/**
 * Adds username metadata to the membership.
 *
 * @internal
 *
 * @param \WC_Memberships_Membership_Plan $membership_plan the Membership Plan
 * @param array $args optional arguments
 */
function add_username_metadata($membership_plan, $args = []) {
    $user_membership_id = isset( $args['user_membership_id'] ) ? absint( $args['user_membership_id'] ) : null;

    if ( ! ( $user_membership = wc_memberships_get_user_membership( $user_membership_id ) ) ) {
        return;
    }
    $username = $user_membership->get_user()->data->user_login;

    update_post_meta( $user_membership_id, 'username', $username );
}

/**
 * Update username metadata when transfer membership.
 *
 * @internal
 *
 * @param \WC_Memberships_User_Membership $user_membership The membership that was transferred from a user to another
 * @param \WP_User $new_owner The membership new owner
 */
function update_username_when_transfer($membership, $new_user) {
    update_post_meta( $user_membership_id->get_id(), 'username', $new_user->data->user_login );
}

/**
 * Adds a webhook action when a membership team is created.
 *
 * @internal
 *
 * @param int $post_id post ID
 * @param \WP_Post $post post object
 * @param bool $updated whether this is an update and not a new post creation
 */
function add_membership_team_created_webhook_action( $post_id, $post, $updated ) {

    if ( 'wc_memberships_team' === get_post_type( $post ) && ! in_array( $post->post_status, [ 'new', 'auto-draft' ], true ) ) {

        if ( ! $updated ) {

            $membership_team_id = (int) $post_id;
            $webhook_key = 'wc_memberships_webhook_membership_team_created';

            if ( ! isset( $sent_webhooks[ $webhook_key ] ) ) {
                $sent_webhooks[ $webhook_key ] = [];
            }

            if ( ! in_array( $membership_team_id, $sent_webhooks[ $webhook_key ], true ) ) {

                /**
                 * Fires when a membership team is created, for webhook use.
                 *
                 * @param int $membership_team_id ID of the membership team created
                 */
                do_action( 'wc_memberships_webhook_membership_team_created', $membership_team_id );

                $sent_webhooks[ $webhook_key ][] = $membership_team_id;
            }

        } else {

            add_membership_team_updated_webhook_action( $post_id, $post );
        }
    }
}


/**
* Adds custom field metaboxes to the array of allowed metaboxes
*
* @param array $allowed required The array of allowed metabox ids
* @return array $allowed The array with custom field metaboxes added in
*/
function allow_custom_field_metaboxes( $allowed ){
    $groups = acf_get_field_groups();

    foreach ($groups as $group) {
        $allowed[] = 'acf-' . $group['key'];
    }

    return $allowed;
}


/**
 * Adds a webhook action when a membership team is updated.
 *
 * @internal
 *
 * @param int $post_id post ID
 * @param \WP_Post $post post object
 */
function add_membership_team_updated_webhook_action( $post_id, $post ) {

    if ( 'wc_memberships_team' === get_post_type( $post ) && ! in_array( $post->post_status, [ 'new', 'auto-draft', 'trash' ], true ) ) {

        $membership_team_id = (int) $post_id;

        $webhook_key = 'wc_memberships_webhook_membership_team_updated';

        if ( ! isset( $sent_webhooks[ $webhook_key ] ) ) {
            $sent_webhooks[ $webhook_key ] = [];
        }

        if ( ! in_array( $membership_team_id, $sent_webhooks[ $webhook_key ], true ) ) {

            /**
             * Fires when a membership team is updated, for webhook use.
             *
             * @param int $membership_team_id ID of the membership team updated
             */
            do_action( 'wc_memberships_webhook_membership_team_updated', $membership_team_id );

            $sent_webhooks[ $webhook_key ][] = $membership_team_id;
        }
    }
}


/**
 * Adds a webhook action when a membership team is sent to trash.
 *
 * @internal
 *
 * @param int $post_id post ID
 */
function add_membership_team_deleted_webhook_action( $post_id ) {

    if ( 'wc_memberships_team' === get_post_type( $post_id ) ) {

        $membership_team_id = (int) $post_id;
        $webhook_key = 'wc_memberships_webhook_membership_team_deleted';

        if ( ! isset( $sent_webhooks[ $webhook_key ] ) ) {
            $sent_webhooks[ $webhook_key ] = [];
        }

        if ( ! in_array( $membership_team_id, $sent_webhooks[ $webhook_key ], true ) ) {

            /**
             * Fires when a membership team is deleted (trashed), for webhook use.
             *
             * @param int $membership_team_id ID of the membership team sent to trash
             */
            do_action( 'wc_memberships_webhook_membership_team_deleted', $membership_team_id );

            $sent_webhooks[ $webhook_key ][] = $membership_team_id;
        }
    }
}


/**
 * Adds a webhook action when a membership team is restored from trash.
 *
 * @internal
 *
 * @param int $post_id post ID
 */
function add_membership_team_restored_webhook_action( $post_id ) {

    if ( 'wc_memberships_team' === get_post_type( $post_id ) ) {

        $membership_team_id = (int) $post_id;
        $webhook_key = 'wc_memberships_webhook_membership_team_restored';

        if ( ! isset( $sent_webhooks[ $webhook_key ] ) ) {
            $sent_webhooks[ $webhook_key ] = [];
        }

        if ( ! in_array( $membership_team_id, $sent_webhooks[ $webhook_key ], true ) ) {

            /**
             * Fires when a membership team is restored from the trash, for webhook use.
             *
             * @param int $membership_team_id ID of the membership team restored
             */
            do_action( 'wc_memberships_webhook_membership_team_restored', $membership_team_id );

            $sent_webhooks[ $webhook_key ][] = $membership_team_id;
        }
    }
}


function get_formatted_item_data( $team ) {
    $wicket_organization = get_field( "wicket_organization", $team->get_id() );
    $start_date = $team->get_date();
    $end_date = $team->get_membership_end_date();
    
    // Check to see if this team has an attached subscription. 
    // If it does, pass the subscription start and end dates to Wicket
    // Else fall back to using the team post "created" and "team memberships begin to expire" dates
    $team_subscription_id = get_post_meta($team->get_id(),'_subscription_id')[0] ?? '';
    if ($team_subscription_id) { 
        // $start_date = get_post_meta($team_subscription_id, '_schedule_start');
        // $end_date = get_post_meta($team_subscription_id, '_schedule_end');    

        $team_subscription = wcs_get_subscription($team_subscription_id);
        $sub_instance = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();
        // $start_date = $sub_instance->get_subscription_event_date( $team_subscription, 'start' );
        $end_date = $sub_instance->get_subscription_event_date( $team_subscription, 'end' );
    }

    $payload = [
        'id' => $team->get_id(),
        'name' => $team->get_name(),
        'owner' => $team->get_owner()->data->user_login,
        'membership_plan' => $team->get_plan(),
        'date' => $start_date,
        'end_date' => $end_date,
        'wicket_organization' => $wicket_organization,
    ];

    return $payload;
}
