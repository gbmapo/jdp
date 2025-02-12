<?php

namespace Drupal\amap\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class DistributionDateTableForm.
 */
class DistributionDateTableForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'distribution_date_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['distributions'] = [
      '#type' => 'table',
//    '#header' => array($this->t('Date')),
      '#header' => [''],
      '#id' => 'calendarofdistributions',
    ];

    _list_distribution_products($aProducts, $sMin, $sMax);
    $fields = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions('distribution_date');
    foreach ($fields as $key => $value) {
      if ($key >= $sMin && $key <= $sMax) {
        // Remplacer le nom des champs product
        $i = (int) str_replace("product", "", $key);
        $temp = $aProducts[$i];
        if (mb_substr($temp, 0, 4) == 'Lait') {
          $newLabel = mb_substr($temp, 5, 4);
        }
        else {
          $newLabel = mb_substr($temp, 0, 4);
        }
        $form['distributions']['#header'][] = ['data' => $newLabel,];
      }
    }

    $currentDay = date('Y-m-d');
    $sNextWed = DrupalDateTime::createFromTimestamp(strtotime("next Wednesday", strtotime("Yesterday")), new \DateTimeZone('Europe/Paris'))
      ->format('Y-m-d');


    $storage = \Drupal::entityTypeManager()->getStorage('distribution_date');
    $database = \Drupal::database();
    $query = $database->select('distribution_date', 'amdd');
    $query->fields('amdd', ['id', 'distributiondate'])
      ->condition('distributiondate', $sNextWed, '>=')
      ->orderBy('distributiondate', 'ASC');
    $ids = $query->execute()->fetchCol(0);
    $dates = $storage->loadMultiple($ids);
    foreach ($dates as $id => $date) {
      foreach ($date as $key => $value) {
        $distributiondate = $date->distributiondate->value;
        $option = 0;
        switch (TRUE) {
          case ($key == 'distributiondate'):
            $form['distributions'][$id]['distributiondate'] = [
              '#markup' => $distributiondate,
            ];
            break;
          case ($key >= $sMin && $key <= $sMax):
            $form['distributions'][$id][$key] = [
              '#type' => 'checkbox',
              '#default_value' => $date->$key->value,
              '#disabled' => ($distributiondate < $currentDay) ? TRUE : FALSE,
            ];
            break;
          default:
        }
      }
    }

    $form['adddates'] = [
      '#type' => 'submit',
      '#name' => 'adddates',
      '#value' => $this->t('Add dates'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'save',
      '#value' => $this->t('Save'),
    ];

    $form['#attached']['library'][] = 'amap/calendar-of-distributions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#name'] == 'save') {
      parent::validateForm($form, $form_state);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    switch ($form_state->getTriggeringElement()['#name']) {

      case 'adddates':

        $lastdistribution = \Drupal::database()
          ->select('distribution_date', 'dd')
          ->fields('dd', ['id', 'distributiondate'])
          ->orderBy('distributiondate', 'DESC')
          ->range(0, 1)
          ->execute()
          ->fetchAssoc();
        $lastdistributiondate = $lastdistribution['distributiondate'];

        for ($i = 1; $i < 6; $i++) {
          $offset = "+ " . (7 * $i) . " days";
          $nextdistributiondate = DrupalDateTime::createFromTimestamp(strtotime($offset, strtotime($lastdistributiondate)), new \DateTimeZone('Europe/Paris'))
            ->format('Y-m-d');
          $entity = \Drupal::entityTypeManager()
            ->getStorage('distribution_date')
            ->create([
              'distributiondate' => $nextdistributiondate,
              'numberofproducts' => 0,
            ]);
          $entity->save();
        }

        \Drupal::messenger()
          ->addMessage($this->t('The dates have been added.'));

        $form_state->setRebuild();

        break;

      case 'save':
        _list_distribution_products($aProducts, $sMin, $sMax);
        foreach ($form_state->getValue('distributions') as $key => $value) {
          $entity = \Drupal::entityTypeManager()
            ->getStorage('distribution_date')
            ->load($key);
          $entity->numberofproducts->value = 0;
          foreach ($entity as $key2 => $value2) {
            if ($key2 >= $sMin && $key2 <= $sMax) {
              $entity->numberofproducts->value += ($entity->$key2->value) ? 1 : 0;
              $entity->$key2->value = $value[$key2];
            }
          }
          $entity->save();
        }

        \Drupal::messenger()
          ->addMessage($this->t('The changes have been saved.'));

        $form_state->setRedirect('view.amap_distributions.page_1');

        break;

      default:
        break;
    }
  }

}
