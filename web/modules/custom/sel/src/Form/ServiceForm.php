<?php

namespace Drupal\sel\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;

/**
 * Form controller for Service edit forms.
 *
 * @ingroup sel
 */
class ServiceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* @var $entity \Drupal\sel\Entity\Service */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['service']['widget']['0']['value']['#size'] = 128;

    $form['picture']['widget']['#open'] = FALSE;

    $form['fileDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('File'),
      '#weight' => 8,
    ];
    $form['file']['#group'] = 'fileDetails';

    $form['link']['widget']['0']['#type'] = 'details';
    $form['link']['widget']['0']['title']['#access'] = FALSE;
    $form['link']['widget']['0']['uri']['#type'] = "url";
    $form['link']['widget']['0']['uri']['#link_type'] = 16;
    $form['link']['widget']['0']['uri']['#description'] = t('This must be an external URL such as %url.', ['%url' => 'http://example.com']);

    unset($form['actions']['delete']);

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->setUrl(),
      '#attributes' => [
        'class' => 'button',
      ],
      '#weight' => '20',
    ];

    return $form;
  }

  public function setUrl() {
    switch ($_GET['origin']) {
      case 1:
        $url = Url::fromRoute('view.sel_services.page_1');
        break;
      case 2:
        $url = Url::fromRoute('view.sel_services.page_2', [
          'arg_0' => \Drupal::currentUser()
            ->id(),
        ]);
        break;
      default:
        break;
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
    if ($form_state->hasAnyErrors()) {
    }
    else {

      $values = $form_state->getValues();
      $sDueDate = $values['duedate'][0]['value']->format("Y-m-d");
      $sToday = DrupalDateTime::createFromTimestamp(strtotime("now"), new \DateTimeZone('Europe/Paris'),)
        ->format('Y-m-d');
      $sIn2Weeks = DrupalDateTime::createFromTimestamp(strtotime("+ 2 weeks"), new \DateTimeZone('Europe/Paris'),)
        ->format('Y-m-d');
      $sIn10Years = DrupalDateTime::createFromTimestamp(strtotime("+ 10 years"), new \DateTimeZone('Europe/Paris'),)
        ->format('Y-m-d');

      if ($values['status']['value'] == 0) {
        //Pas de contrôle si le service n'est pas publié
      }
      else {
        if ($sDueDate <= $sToday) {
          $form_state->setErrorByName('duedate', $this->t('Due date must be in the future.'));
        }
        else {
          if ($values['isurgent']['value'] == 1) {
            if ($sDueDate > $sIn2Weeks) {
              $form_state->setErrorByName('duedate', $this->t('It is no longer quite urgent!<BR>Please change the due date or uncheck \'Urgent\'.'));
            }
          }
          else {
            if ($sDueDate > $sIn10Years) {
              $form_state->setErrorByName('duedate', $this->t('Validity period cannot exceed ten years!'));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    $values = $form_state->getValues();
    if (($values['isurgent']['value'] == 1) && ($values['status']['value'] == 1)) {
      $iSeliste = $entity->owner_id->target_id;
      $oSeliste = \Drupal::entityTypeManager()
        ->getStorage('person')
        ->load($iSeliste);
      $sSeliste = $oSeliste->firstname->value . " " . $oSeliste->lastname->value;
      $sAction = ($values['action'][0]['value'] == 'O') ? "offre" : "demande";
      $sService = $values['service'][0]['value'];
      $sDueDate = $values['duedate'][0]['value']->format("d/m/Y");
      _sendEmailForUrgentService($sSeliste, $sAction, $sService, $sDueDate);
    }

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()
          ->addMessage($this->t('Service « @label » has been added.', [
            '@label' => $entity->label(),
          ]));
        break;

      case SAVED_UPDATED:
        \Drupal::messenger()
          ->addMessage($this->t('Service « @label » has been updated.', [
            '@label' => $entity->label(),
          ]));
        break;

      default:
        break;
    }

    $form_state->setRedirectUrl($this->setUrl());

  }

}
