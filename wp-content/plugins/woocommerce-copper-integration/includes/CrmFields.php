<?php
namespace IwantToBelive\Wc\Copper\Integration\Includes;

class CrmFields
{
    public static $fields = [
        'lead' => [
            'title',
            'assignee_id',
            'first_name',
            'middle_name',
            'last_name',
            'prefix',
            'suffix',
            'company_name',
            'address' => [
                'street',
                'city',
                'state',
                'postal_code',
                'country'
            ],
            'customer_source_id',
            'details',
            'email',
            'phone_numbers',
            'tags'
        ],
        'opportunity' => [
            'name',
            'assignee_id',
            'customer_source_id',
            'details',
            'tags'
        ],
        'person' => [
            'name',
            'first_name',
            'middle_name',
            'last_name',
            'prefix',
            'suffix',
            'address' => [
                'street',
                'city',
                'state',
                'postal_code',
                'country'
            ],
            'contact_type_id',
            'details',
            'emails',
            'phone_numbers',
            'tags'
        ],
        'organization' => [
            'name',
            'address' => [
                'street',
                'city',
                'state',
                'postal_code',
                'country'
            ],
            'contact_type_id',
            'details',
            'email_domain',
            'phone_numbers',
            'tags'
        ]
    ];

    public static $requiredFields = [
        'lead' => [
            'first_name'
        ],
        'opportunity' => [
            'name'
        ],
        'person' => [
            'name'
        ],
        'organization' => [
            'name'
        ]
    ];

    public function __construct()
    {
        $this->fieldLabels = [
            'name' => esc_html__('Name', 'wc-copper-integration'),
            'title' => esc_html__('Title', 'wc-copper-integration'),
            'first_name' => esc_html__('First Name', 'wc-copper-integration'),
            'middle_name' => esc_html__('Middle Name', 'wc-copper-integration'),
            'last_name' => esc_html__('Last Name', 'wc-copper-integration'),
            'prefix' => esc_html__('Prefix', 'wc-copper-integration'),
            'suffix' => esc_html__('Suffix', 'wc-copper-integration'),
            'company_name' => esc_html__('Company', 'wc-copper-integration'),
            'street' => esc_html__('Street', 'wc-copper-integration'),
            'city' => esc_html__('City', 'wc-copper-integration'),
            'state' => esc_html__('State', 'wc-copper-integration'),
            'postal_code' => esc_html__('Zip', 'wc-copper-integration'),
            'country' => esc_html__('Country', 'wc-copper-integration'),
            'customer_source_id' => esc_html__('Source', 'wc-copper-integration'),
            'details' => esc_html__('Description', 'wc-copper-integration'),
            'email' => esc_html__('Email', 'wc-copper-integration'),
            'emails' => esc_html__('Email', 'wc-copper-integration'),
            'phone_numbers' => esc_html__('Phone', 'wc-copper-integration'),
            'monetary_value' => esc_html__('Value', 'wc-copper-integration'),
            'status_id' => esc_html__('Status', 'wc-copper-integration'),
            'tags' => esc_html__('Tags', 'wc-copper-integration'),
            'contact_type_id' => esc_html__('Contact Type', 'wc-copper-integration'),
            'email_domain' => esc_html__('Email Domain', 'wc-copper-integration'),
            'assignee_id' => esc_html__('Owner', 'wc-copper-integration'),
            'pipeline_id' => esc_html__('Pipeline - Stage', 'wc-copper-integration')
        ];
    }

    private function __clone()
    {
    }
}
