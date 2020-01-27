<?php

  namespace Drupal\access_control\Form;

  use Drupal\Core\Form\ConfigFormBase;
  use Drupal\Core\Form\FormStateInterface;

   class AccessControlForm extends ConfigFormBase {


    const AC_SETTINGS = 'access_control.settings';
     /**
      * {@inheritdoc}
     */
     public function getFormId() {
       return 'access_control_settings';
     }

    protected function getEditableConfigNames() {
        return [
            static::AC_SETTINGS,
        ];
    }

     /**
      * {@inheritdoc}
     */

     public function buildForm(array $form, FormStateInterface $form_state) {

        $config = $this->config(static::AC_SETTINGS);

        $form['lockdown'] = [
            '#type' => 'checkbox',
            '#title' => 'Enable lockdown',
            '#description' => 'Check this box to prevent anonymous viewers',
            '#default_value' => $config->get('lockdown'),
        ];
        return parent::buildForm($form, $form_state);
     }

     /**
      * {@inheritdoc}
     */

     public function validateForm(array &$form, FormStateInterface $form_state) {
       // Add validation rules in here.
     }

     /**
      * {@inheritdoc}
     */

     public function submitForm(array &$form, FormStateInterface $form_state) {

       $this->configFactory->getEditable(static::AC_SETTINGS)
       ->set('lockdown', $form_state->getValue('lockdown'))
       ->save();
       parent::submitForm($form, $form_state);

       // @todo - this should probably just invalidate the ac:response cache tag
       drupal_flush_all_caches();
   }

 }
