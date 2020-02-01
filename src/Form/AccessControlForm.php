<?php

namespace Drupal\access_control\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Access control form class.
 */
class AccessControlForm extends ConfigFormBase {

  const SETTINGS = 'access_control.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_control_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(static::SETTINGS);

    $form['lockdown'] = [
      '#type' => 'checkbox',
      '#title' => 'Enable lockdown',
      '#description' => $this->t('Check this box to prevent anonymous viewers'),
      '#default_value' => $config->get('lockdown'),
    ];

    $form['lockdown_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Displays during lockdown mode'),
      '#default_value' => $config->get('lockdown_message'),
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
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('lockdown', $form_state->getValue('lockdown'))
      ->set('lockdown_message', $form_state->getValue('lockdown_message'))
      ->save();
    parent::submitForm($form, $form_state);
    // @todo - this should probably just invalidate the ac:response cache tag
    drupal_flush_all_caches();
  }

}
