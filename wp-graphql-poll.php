<?php
/**
 * Plugin name: WPGraphPoll
 * Version: 0.0.1-beta
 * Author: Thong Thai, WPGraphQL, Andre Noberto, Adrien Becuwe, 7aduta, Lester Chan
 * Author URI: https://github.com/nikethai
 * Description: This is an updated version of WP GraphQL Polls
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace WPGraphQL\Polls;

use WPGraphQL\Registry\TypeRegistry;

defined('ABSPATH') or die('Die Die Die!');
define('PLUGIN_DIR', dirname(__FILE__));

require_once(PLUGIN_DIR . '/src/poll.php');
require_once(PLUGIN_DIR . '/src/vote.php');

// PollAnswer Type
add_action('graphql_register_types', function (TypeRegistry $typeRegistry) {
    register_graphql_object_type('PollAnswer', [
        'description' => __('Return a list of answers', 'wp-graphql-polls'),
        'fields' => [
            'id' => [
                'type' => "Integer",
                'description' => __('The id for voting references.', 'wp-graphql-polls')
            ],
            'description' => [
                'type' => "String",
                'description' => __('A choosable option for the current poll.', 'wp-graphql-polls')
            ],
            'votes' => [
                'type' => "Integer",
                'description' => __('The current number of votes for this option.', 'wp-graphql-polls')
            ]
        ]
    ]);
});

// PollList Type
add_action('graphql_register_types', function (TypeRegistry $typeRegistry) {
    register_graphql_object_type('PollList', [
        'description' => __('Returns a list of the existing polls', 'wp-graphql-polls'),
        'fields' => [
            'id' => [
                'type' => 'Integer',
                'description' => __('The poll id', 'wp-graphql-polls')
            ],
            'question' => [
                'type' => 'String',
                'description' => __('The poll\'s question', 'wp-graphql-polls')
            ],
            'totalVotes' => [
                'type' => 'Integer',
                'description' => __('The current number of votes', 'wp-graphql-polls')
            ],
            'answers' => [
                'type' => ['list_of' => 'PollAnswer'],
                'description' => __('The answers for the current poll', 'wp-graphql-polls')
            ],
            'open' => [
                'type' => 'Boolean',
                'description' => __('Tells if the current poll is available for voting or not', 'wp-graphql-polls')
            ],
            'maxAnswers' => [
                'type' => 'Integer',
                'description' => __('The maximum number of answers allowed in this poll', 'wp-graphql-polls')
            ],
            'voted' => [
                'type' => 'Boolean',
                'description' => __('Tells if the user has voted or not', 'wp-graphql-polls')
            ]
        ]
    ]);
});

//Polls Field (incl PollAnswer Type + PollList Type)
add_action('graphql_register_types', function (TypeRegistry $typeRegistry) {
    register_graphql_field('RootQuery', 'polls', [
        'type' => ['list_of' => 'PollList'],
        'args' => [
            'id' => [
                'type' => 'Integer',
                'description' => __('The poll id you want to query', 'wp-graphql-polls')
            ],
        ],
        'description' => __('Returns a list of the existing polls', 'wp-graphql-polls'),
        'resolve' => function () {
            return Poll::get_polls();
        }
    ]);
});

// Return default value
add_filter('graphql_resolve_field', function ($result, $source, $args, $context, $info, $type_name, $field_key, $field, $field_resolver) {
    if ('polls' === $field_key && !empty($args['id'])) {
        $result = Poll::get_poll_by_id($args['id']);
    }

    return $result;
}, 10, 9);

// Mutation of polls (vote)
add_action('graphql_register_types', function () {
    register_graphql_mutation('vote', [
        'description' => __('Inserts a vote in a poll', 'wp-graphql-polls'),
        'inputFields' => [
            'id' => [
                'type' => ['non_null' => 'Integer'],
                'description' => __('The poll id you want to insert a vote', 'wp-graphql-polls')
            ],
            'userId' => [
                'type' => ['non_null' => 'Integer'],
                'description' => __('The user id that are voting', 'wp-graphql-polls')
            ],
            'answers' => [
                'type' => ['non_null' => 'String'],
                'description' => __('The answers you are voting.', 'wp-graphql-polls')
            ],
        ],
        'outputFields' => [
            'status' => [
                'type' => 'Integer',
                'description' => __('The status code of the request.', 'wp-graphql-polls')
            ],
            'message' => [
                'type' => 'String',
                'description' => __('Describes the status of the request.', 'wp-graphql-polls')
            ]
        ],
        'mutateAndGetPayload' => function ($input) {

            /**
             * Register a vote and returns a confirmation for the user
             */
            return Vote::vote($input['id'], $input['userId'], trim($input['answers']));
        }
    ]);
});