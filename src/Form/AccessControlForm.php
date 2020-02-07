<?php

namespace Drupal\access_control\Form;

use Drupal\access_control\AccessControlLockdown;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control form class.
 */
class AccessControlForm extends ConfigFormBase {

  const SETTINGS = 'access_control.settings';

  /**
   * Access Control service.
   *
   * @var Drupal\access_control\AccessControlLockdown
   */
  protected $accessControl;

  /**
   * Constructor to inject services.
   */
  public function __construct(AccessControlLockdown $accessControl) {
    $this->accessControl = $accessControl;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $accessControl = $container->get('access_control.lockdown');
    return new static($accessControl);
  }

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
      '#title' => $this->t('Enable Lockdown'),
      '#description' => $this->t('Check this box to prevent anonymous viewers'),
      '#default_value' => $config->get('lockdown'),
    ];

    $form['lockdown_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Displays during lockdown mode'),
      '#default_value' => $config->get('lockdown_message'),
    ];

    $form['lockdown_preview_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose Content Type'),
      '#description' => $this->t('Select the type of content to show in lockdown mode'),
      '#options' => $this->accessControl->generateNodeTypeList(),
      '#default_value' => $config->get('lockdown_preview_type'),
    ];

    $form['lockdown_preview_sports_headlines'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose Sport'),
      '#description' => $this->t('Which league headlines would you like to show?'),
      '#options' => $this->accessControl->generateTaxonomyTermList(),
      '#default_value' => $config->get('lockdown_preview_sports_headlines'),
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
      ->set('lockdown_preview_type', $form_state->getValue('lockdown_preview_type'))
      ->set('lockdown_preview_sports_headlines', $form_state->getValue('lockdown_preview_sports_headlines'))
      ->save();
    parent::submitForm($form, $form_state);
    // @todo - this should probably just invalidate the ac:response cache tag
    drupal_flush_all_caches();
  }

}
