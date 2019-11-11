<?php
namespace IwantToBelive\Wc\Copper\Integration\Admin;

use IwantToBelive\Wc\Copper\Integration\Includes\Bootstrap;
use IwantToBelive\Wc\Copper\Integration\Includes\CrmFields;

class OpportunitySettings
{
    private static $instance = false;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function __construct()
    {

    }

    public function render($meta)
    {
        $renderFields = new RenderFields('opportunity');
        $currentValues = isset($meta['opportunity']) ? $meta['opportunity'] : [];
        $crmFields = new CrmFields();
        ?>
        <table class="form-table">
            <?php
            $leadFields = $crmFields::$fields['opportunity'];
            $fieldLabels = $crmFields->fieldLabels;

            foreach ($leadFields as $keyField => $field) {
                if (is_array($field)) {
                    $currentValue = isset($currentValues[$keyField]) ? $currentValues[$keyField] : '';

                    foreach ($field as $subField) {
                        ?>
                        <tr>
                            <th>
                                <?php echo esc_html($fieldLabels[$subField]); ?>
                                <?php
                                echo in_array($field, CrmFields::$requiredFields['opportunity'])
                                    ? '<span style="color:red;"> * </span>'
                                    : '';
                                ?>
                            </th>
                            <td>
                                <?php
                                $subValue = isset($currentValue[$subField]) ? $currentValue[$subField] : '';

                                $renderFields->inputTextField(
                                    $keyField . '][' . $subField,
                                    $fieldLabels[$subField],
                                    $subValue
                                );
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <th>
                            <?php echo esc_html($fieldLabels[$field]); ?>
                            <?php
                            echo in_array($field, CrmFields::$requiredFields['opportunity'])
                                ? '<span style="color:red;"> * </span>'
                                : '';
                            ?>
                        </th>
                        <td>
                            <?php
                            $currentValue = isset($currentValues[$field]) ? $currentValues[$field] : '';

                            if ($field == 'customer_source_id') {
                                $selectItems = [];

                                $items = get_option(Bootstrap::CUSTOMER_SOURCES_KEY);

                                foreach ($items as $item) {
                                    $selectItems[$item['id']] = $item['name'];
                                }

                                $renderFields->selectField(
                                    $selectItems,
                                    $field,
                                    $fieldLabels[$field],
                                    $currentValue
                                );
                            } elseif ($field == 'assignee_id') {
                                $selectItems = [];

                                $items = get_option(Bootstrap::USER_LIST_KEY);

                                foreach ($items as $item) {
                                    $selectItems[$item['id']] = $item['name'];
                                }

                                $renderFields->selectField(
                                    $selectItems,
                                    $field,
                                    $fieldLabels[$field],
                                    $currentValue
                                );
                            } elseif ($field == 'details') {
                                $renderFields->textareaField(
                                    $field,
                                    $fieldLabels[$field],
                                    $currentValue
                                );
                            } else {
                                $renderFields->inputTextField(
                                    $field,
                                    $fieldLabels[$field],
                                    $currentValue
                                );
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
            }
            $this->showCustomFields($renderFields, $currentValues, 'opportunity');
            ?>
        </table>
        <?php
    }

    public function showCustomFields($renderFields, $currentValues, $type)
    {
        $fields = get_option(Bootstrap::CUSTOM_FIELDS_KEY);
        $currentValues = isset($currentValues['custom_fields']) ? $currentValues['custom_fields'] : [];

        foreach ($fields as $keyField => $field) {
            if (!in_array($type, $field['available_on'])) {
                continue;
            }
            ?>
            <tr>
                <th>
                    <?php echo esc_html($field['name'] . ' (' . $field['data_type'] . ')'); ?>
                </th>
                <td>
                    <?php
                    $currentValue = isset($currentValues[$field['id']]) ? $currentValues[$field['id']] : '';
                    $currentValuePopulate = isset($currentValues[$field['id']. '-populate'])
                        ? $currentValues[$field['id']. '-populate']
                        : '';

                    if (in_array($field['data_type'], ['MultiSelect', 'Dropdown'])) {
                        $selectItems = [];

                        foreach ($field['options'] as $item) {
                            $selectItems[$item['id']] = $item['name'];
                        }

                        $renderFields->selectFieldWithPopulate(
                            $selectItems,
                            'custom_fields][' . $field['id'],
                            $field['name'],
                            $currentValue,
                            $currentValuePopulate
                        );
                    } elseif ($field['data_type'] == 'Text') {
                        $renderFields->textareaField(
                            'custom_fields][' . $field['id'],
                            $field['name'],
                            $currentValue
                        );
                    } elseif ($field['data_type'] == 'Checkbox') {
                        $renderFields->inputCheckboxField(
                            'custom_fields][' . $field['id'],
                            $field['name'],
                            $currentValue
                        );
                    } else {
                        $renderFields->inputTextField(
                            'custom_fields][' . $field['id'],
                            $field['name'],
                            $currentValue
                        );
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
    }
}
