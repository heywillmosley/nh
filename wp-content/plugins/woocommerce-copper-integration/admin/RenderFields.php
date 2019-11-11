<?php
namespace IwantToBelive\Wc\Copper\Integration\Admin;

class RenderFields
{
    public $fieldNameStart = '';

    public function __construct($type = '')
    {
        $this->fieldNameStart .= $type . '[';
    }

    public function selectField($list, $name, $title, $currentValue)
    {
        ?>
        <select id="__<?php echo esc_attr($name); ?>"
            title="<?php echo esc_attr($title); ?>"
            name="<?php echo esc_attr($this->fieldNameStart . $name); ?>]">
            <option value=""><?php esc_html_e('Not chosen', 'wc-copper-integration'); ?></option>
            <?php
            foreach ((array) $list as $value => $name) {
                echo '<option value="'
                    . esc_attr($value)
                    . '"'
                    . ($currentValue == $value ? ' selected' : '')
                    . '>'
                    . esc_html($value . ' - ' . $name)
                    . '</option>';
            }
            ?>
        </select>
        <?php
    }

    public function selectFieldWithPopulate($list, $name, $title, $currentValue, $currentValuePopulate = '')
    {
        ?>
        <table width="100%">
            <tr>
                <td style="width: 50%;">
                    <label><?php esc_html_e('Default value', 'wc-copper-integration'); ?></label>
                    <br>
                    <select id="__<?php echo esc_attr($name); ?>"
                        title="<?php echo esc_attr($title); ?>"
                        name="<?php echo esc_attr($this->fieldNameStart . $name); ?>]">
                        <option value=""><?php esc_html_e('Not chosen', 'wc-copper-integration'); ?></option>
                        <?php
                        foreach ((array) $list as $value => $label) {
                            echo '<option value="'
                                . esc_attr($value)
                                . '"'
                                . ($currentValue == $value ? ' selected' : '')
                                . '>'
                                . esc_html($value . ' - ' . $label)
                                . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td>
                    <label><?php esc_html_e('Form value (optional)', 'wc-copper-integration'); ?></label>
                    <br>
                    <input id="__<?php echo esc_attr($name); ?>-populate"
                        type="text"
                        class="large-text code"
                        title="<?php echo esc_attr($title); ?>"
                        name="<?php echo esc_attr($this->fieldNameStart . $name); ?>-populate]"
                        value="<?php echo esc_attr($currentValuePopulate); ?>">
                </td>
            </tr>
        </table>
        <?php
    }

    public function inputTextField($name, $title, $currentValue, $placeholder = '')
    {
        ?>
        <input id="__<?php echo esc_attr($name); ?>"
            type="text"
            class="large-text code"
            title="<?php echo esc_attr($title); ?>"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            name="<?php echo esc_attr($this->fieldNameStart . $name); ?>]"
            value="<?php echo esc_attr($currentValue); ?>">
        <?php
    }

    public function inputCheckboxField($name, $title, $currentValue)
    {
        ?>
        <input type="hidden"
            name="<?php echo esc_attr($this->fieldNameStart . $name); ?>]"
            value="N">
        <input id="__<?php echo esc_attr($name); ?>"
            type="checkbox"
            title="<?php echo esc_attr($title); ?>"
            name="<?php echo esc_attr($this->fieldNameStart . $name); ?>]"
            value="Y"
            <?php echo $currentValue === 'Y' ? 'checked' : ''; ?>>
        <?php
    }

    public function textareaField($name, $title, $currentValue)
    {
        ?>
        <textarea
            id="__<?php echo esc_attr($name); ?>"
            class="large-text code"
            title="<?php echo esc_attr($title); ?>"
            name="<?php echo esc_attr($this->fieldNameStart . $name); ?>]"
            rows="4"><?php echo esc_attr($currentValue); ?></textarea>
        <?php
    }
}
