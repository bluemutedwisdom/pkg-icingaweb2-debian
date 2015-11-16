<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Exception;
use Zend_Form_Decorator_Abstract;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Application\Config;

/**
 * Form base-class providing standard functionality for configuration forms
 */
class ConfigForm extends Form
{
    /**
     * The configuration to work with
     *
     * @var Config
     */
    protected $config;

    /**
     * Set the configuration to use when populating the form or when saving the user's input
     *
     * @param   Config      $config     The configuration to use
     *
     * @return  $this
     */
    public function setIniConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $sections = array();
        foreach ($this->getValues() as $sectionAndPropertyName => $value) {
            if ($value === '') {
                $value = null; // Causes the config writer to unset it
            }

            list($section, $property) = explode('_', $sectionAndPropertyName, 2);
            $sections[$section][$property] = $value;
        }

        foreach ($sections as $section => $config) {
            $this->config->setSection($section, $config);
        }

        if ($this->save()) {
            Notification::success($this->translate('New configuration has successfully been stored'));
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onRequest()
    {
        $values = array();
        foreach ($this->config as $section => $properties) {
            foreach ($properties as $name => $value) {
                $values[$section . '_' . $name] = $value;
            }
        }

        $this->populate($values);
    }

    /**
     * Persist the current configuration to disk
     *
     * If an error occurs the user is shown a view describing the issue and displaying the raw INI configuration.
     *
     * @return  bool                    Whether the configuration could be persisted
     */
    public function save()
    {
        try {
            $this->writeConfig($this->config);
        } catch (Exception $e) {
            $this->addDecorator('ViewScript', array(
                'viewModule'    => 'default',
                'viewScript'    => 'showConfiguration.phtml',
                'errorMessage'  => $e->getMessage(),
                'configString'  => $this->config,
                'filePath'      => $this->config->getConfigFile(),
                'placement'     => Zend_Form_Decorator_Abstract::PREPEND
            ));
            return false;
        }

        return true;
    }

    /**
     * Write the configuration to disk
     *
     * @param   Config  $config
     */
    protected function writeConfig(Config $config)
    {
        $config->saveIni();
    }
}
