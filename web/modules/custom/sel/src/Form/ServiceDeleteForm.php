<?php

namespace Drupal\sel\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Service entities.
 *
 * @ingroup sel
 */
class ServiceDeleteForm extends ContentEntityDeleteForm
{

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $entity = $this->getEntity();
    $entity->delete();

    $form_state->setRedirectUrl($this->setUrl());
    \Drupal::messenger()->addMessage($this->getDeletionMessage());
  }

  public function setUrl() {
    switch ($_GET['origin']) {
      case 1:
        $url = \Drupal\Core\Url::fromRoute('view.sel_services.page_1');
        break;
      case 2:
        $url = \Drupal\Core\Url::fromRoute('view.sel_services.page_2', [
          'arg_0' => \Drupal::currentUser()
            ->id(),
        ]);
        break;
      default:
        break;
    }
    return $url;
  }

  public function getQuestion()
  {
    return $this->t('Are you sure you want to delete service « @label »?', array(
      '@label' => $this->getEntity()->label()
    ));
  }

  public function getCancelUrl()
  {
    return $this->setUrl();
  }

  protected function getDeletionMessage()
  {
    $entity = $this->getEntity();
    \Drupal::messenger()->addMessage($this->t('Service « @label » has been deleted.', array(
      '@label' => $entity->label()
    )));
  }
}
