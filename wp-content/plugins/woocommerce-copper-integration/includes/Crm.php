<?php
namespace IwantToBelive\Wc\Copper\Integration\Includes;

class Crm
{
    private static $startApiLink = 'https://api.prosperworks.com/developer_api/v1/';

    public static function send($sendFields, $currentType = 'lead')
    {
        $result = [];

        switch ($currentType) {
            case 'lead':
                return self::sendApiPostRequest('leads', self::prepareFields($sendFields['lead']));
            case 'opportunity':
                // Find or create company
                if (!empty($sendFields['organization'])) {
                    $organizationID = self::findOrCreateOrganization($sendFields);

                    // Set organization for person and opportunity
                    if ($organizationID) {
                        $sendFields['person']['company_id'] = $organizationID;
                        $sendFields['opportunity']['company_id'] = $organizationID;
                    }
                }

                // Find or create person
                if (!empty($sendFields['person'])) {
                    $personID = self::findOrCreatePerson($sendFields);

                    // Set person for opportunity
                    if ($personID) {
                        $sendFields['opportunity']['primary_contact_id'] = $personID;
                    }
                }

                if (!empty($sendFields['opportunity']['pipeline_id'])) {
                    $pipelineStage = explode('_', $sendFields['opportunity']['pipeline_id']);

                    $sendFields['opportunity']['pipeline_id'] = $pipelineStage[0];
                    $sendFields['opportunity']['pipeline_stage_id'] = $pipelineStage[1];
                }

                return self::sendApiPostRequest(
                    'opportunities',
                    self::prepareFields($sendFields['opportunity'])
                );
            default:
                // Nothing
                break;
        }

        return $result;
    }

    public static function findOrCreateOrganization($sendFields)
    {
        if (!empty($sendFields['organization']['name'])) {
            $query = [
                'name' => $sendFields['organization']['name']
            ];

            $organization = self::sendApiPostRequest('companies/search', $query);

            // If find organization and count 1
            if (!empty($organization)
                && count($organization) == 1
            ) {
                return $organization[0]['id'];
            }
        }

        if (!empty($sendFields['organization']['phone_numbers'])) {
            $query = [
                'phone_number' => $sendFields['organization']['phone_numbers']
            ];

            $organization = self::sendApiPostRequest('companies/search', $query);

            // If find organization and count 1
            if (!empty($organization)
                && count($organization) == 1
            ) {
                return $organization[0]['id'];
            }
        }

        $organization = self::sendApiPostRequest('companies', self::prepareFields($sendFields['organization']));

        // If organization success created
        if (!empty($organization['id'])) {
            return $organization['id'];
        }

        return false;
    }

    public static function findOrCreatePerson($sendFields)
    {
        if (!empty($sendFields['person']['emails'])) {
            $query = [
                'email' => $sendFields['person']['emails']
            ];

            $person = self::sendApiPostRequest('people/fetch_by_email', $query);

            // If find organization and count 1
            if (!empty($person['id'])) {
                return $person['id'];
            }
        }

        if (!empty($sendFields['person']['phone_numbers'])) {
            $query = [
                'phone_number' => $sendFields['person']['phone_numbers']
            ];

            $person = self::sendApiPostRequest('people/search', $query);

            // If find organization and count 1
            if (!empty($person)
                && count($person) == 1
            ) {
                return $person[0]['id'];
            }
        }

        $person = self::sendApiPostRequest('people', self::prepareFields($sendFields['person']));

        // If organization success created
        if (!empty($person['id'])) {
            return $person['id'];
        }

        return false;
    }

    public static function prepareFields($sendFields)
    {
        $returnFields = [];

        foreach ($sendFields as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if ($key == 'email') {
                $returnFields[$key] = [
                    'email' => $value,
                    'category' => 'work'
                ];
            } elseif (in_array($key, ['emails'])) {
                $returnFields[$key] = [
                    [
                        'email' => $value,
                        'category' => 'work'
                    ]
                ];
            } elseif (in_array($key, ['phone_numbers'])) {
                $returnFields[$key] = [
                    [
                        'number' => $value,
                        'category' => 'work'
                    ]
                ];
            } elseif ($key == 'custom_fields') {
                $customFields = [];

                foreach ($value as $fieldID => $fieldValue) {
                    if (empty($fieldValue)) {
                        continue;
                    }

                    $customFields[] = [
                        'custom_field_definition_id' => $fieldID,
                        'value' => $fieldValue
                    ];
                }

                $returnFields[$key] = $customFields;
            } elseif ($key == 'tags') {
                $returnFields[$key] = explode(',', $value);
            } else {
                $returnFields[$key] = $value;
            }

            if ($key == 'details' && !Helper::isVerify()) {
                if (empty($returnFields[$key])) {
                    $returnFields[$key] = Helper::nonVerifyText();
                } else {
                    $returnFields[$key] = Helper::nonVerifyText()
                        . "\n\n"
                        . $returnFields[$key];
                }
            }
        }

        return $returnFields;
    }

    public static function checkConnection()
    {
        $settings = get_option(Bootstrap::OPTIONS_KEY);
        $body = self::sendGetApiRequest('account');

        if (empty($body)) {
            // Clean failed information
            $settings['token'] = '';
            $settings['email'] = '';

            // Clean failed information
            update_option(
                Bootstrap::OPTIONS_KEY,
                $settings
            );

            return sprintf(
                '<div data-ui-component="wccoppernotice" class="error notice notice-error">'
                . '<p><strong>'
                . esc_html__(
                    'Response Copper CRM',
                    'wc-copper-integration'
                )
                . ' (%s)</strong>: %s</p></div>',
                0,
                esc_html__(
                    'Empty response. Check the API token and email. '
                    . 'An error occurred while verifying the connection to the Copper CRM.',
                    'wc-copper-integration'
                )
            );
            // Escape ok
        }

        if (!empty($body['error'])) {
            // Clean failed information
            $settings['token'] = '';
            $settings['email'] = '';

            // Clean failed information
            update_option(
                Bootstrap::OPTIONS_KEY,
                $settings
            );

            return sprintf(
                '<div data-ui-component="wccoppernotice" class="error notice notice-error">'
                . '<p><strong>'
                . esc_html__(
                    'Response Copper CRM',
                    'wc-copper-integration'
                )
                . ' (%s)</strong>: %s</p></div>',
                $body['errorCode'],
                $body['error']
                . '. '
                . esc_html__(
                    'An error occurred while verifying the connection to the Copper CRM.',
                    'wc-copper-integration'
                )
            );
            // Escape ok
        }

        return '';
    }

    public static function updateInformation()
    {
        $apiResponse = self::sendApiPostRequest('users/search', ['page_size' => 200]);

        if ($apiResponse && empty($apiResponse['error'])) {
            update_option(Bootstrap::USER_LIST_KEY, $apiResponse);
        }

        self::updateList('lead_statuses', Bootstrap::LEAD_STATUSES_KEY);
        self::updateList('customer_sources', Bootstrap::CUSTOMER_SOURCES_KEY);
        self::updateList('contact_types', Bootstrap::CONTACT_TYPES_KEY);
        self::updateList('pipelines', Bootstrap::PIPELINES_KEY);
        self::updateList('custom_field_definitions', Bootstrap::CUSTOM_FIELDS_KEY);
    }

    public static function updateList($method, $optionKey)
    {
        $apiResponse = self::sendGetApiRequest($method);

        if ($apiResponse && empty($apiResponse['error'])) {
            update_option($optionKey, $apiResponse);
        }
    }

    public static function sendGetApiRequest($method)
    {
        $response = wp_remote_get(
            self::$startApiLink . $method,
            [
                'headers' => self::generateHeaders()
            ]
        );

        if (is_wp_error($response)) {
            return [];
        }

        $body = $response['body'];

        if (!empty($body)) {
            return json_decode($body, true);
        }

        return [];
    }

    public static function sendApiPostRequest($method, $fields = [])
    {
        $response = wp_remote_post(
            self::$startApiLink . $method,
            [
                'body' => json_encode($fields),
                'headers' => self::generateHeaders()
            ]
        );

        if (is_wp_error($response)) {
            return [];
        }

        $body = $response['body'];

        if (!empty($body)) {
            return json_decode($body, true);
        }

        return [];
    }

    public static function sendApiPutRequest($method, $fields = [])
    {
        $response = wp_remote_request(
            self::$startApiLink . $method,
            [
                'method' => 'PUT',
                'body' => json_encode($fields),
                'headers' => self::generateHeaders()
            ]
        );

        if (is_wp_error($response)) {
            return [];
        }

        $body = $response['body'];

        if (!empty($body)) {
            return json_decode($body, true);
        }

        return [];
    }

    private static function generateHeaders()
    {
        $settings = get_option(Bootstrap::OPTIONS_KEY);

        return [
            'X-PW-AccessToken' => $settings['token'],
            'X-PW-Application' => 'developer_api',
            'X-PW-UserEmail' => $settings['email'],
            'Content-Type' => 'application/json'
        ];
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
