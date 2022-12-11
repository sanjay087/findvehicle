<?php

namespace Drupal\findvehicle\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
// Use for Ajax.
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Implements an simple form.
 */
class FindVehicleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'findvehicle_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $range = range(2023, 1995);
    $yearnames = array_combine($range, $range);
    $form['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#options' => $yearnames,
      '#empty_option' => $this->t('1 | Year'),
      '#ajax' => [
        'callback' => [$this, 'updateMake'],
        'wrapper' => 'make-wrapper',
      ],
    ];
    $yearval = $form_state->getValue('year');
    if ($yearval) {
      $form['make'] = [
        '#type' => 'select',
        '#title' => $this->t('Make'),
        '#prefix' => '<div id="make-wrapper" class="make-class">',
        '#suffix' => '</div>',
        '#options' => [$this->t('2 | Make')],
        '#ajax' => [
          'callback' => [$this, 'updateModel'],
          'wrapper' => 'model-wrapper',
        ],
      ];
    }
    else {
      $form['make'] = [
        '#type' => 'select',
        '#title' => $this->t('Make'),
        '#empty_option' => $this->t('2 | Make'),
        '#prefix' => '<div id="make-wrapper" class="make-class">',
        '#suffix' => '</div>',
        '#disabled' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'updateModel'],
          'wrapper' => 'model-wrapper',
        ],
      ];
    }
    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Model'),
      '#prefix' => '<div id="model-wrapper" class="model-class">',
      '#suffix' => '</div>',
      '#empty_option' => $this->t('3 | Model'),
      '#disabled' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function updateMake(array &$form, FormStateInterface $form_state) {
    $this->yearval = $form_state->getValue('year');
    if (!is_numeric($this->yearval)) {
      $this->yearval = 0;
    }
    // HTTP client request to get actual data.
    $make_options = [];
    $client = \Drupal::httpClient();
    $response_api = $client->request('GET', 'https://vpic.nhtsa.dot.gov/api/vehicles/GetMakesForManufacturerAndYear/mer?year=' . $this->yearval . '&format=json');

    // To get response body content and JSON decoding it.
    $json_array_data = json_decode($response_api->getBody()->getContents());
    $count = $json_array_data->Count;
    if ($count > 0) {
      $results = $json_array_data->Results;
      if (!empty($results)) {
        foreach ($results as $result) {
          $make_options[$result->MakeId] = $result->MakeName;
        }
      }
      asort($make_options);
      $options_name[] = ['_none' => '- None -'];
      $options = '<option value="">' . $this->t('2 | Make') . '</option>';
      foreach ($make_options as $make_id => $value) {
        $options_name[] = [$make_id => $value];
        $options .= '<option value="' . $make_id . '">' . $value . '</option>';
      }

      $options_name = call_user_func_array('array_merge', $options_name);

      $form['make']['#options'] = $options_name;
      $form['model']['#options'] = [];
      // Result Handling.
      $response = new AjaxResponse();
      // Populate the dropdown.
      $response->addCommand(new InvokeCommand('.form-item-make', 'removeClass', ['form-disabled']));
      $response->addCommand(new InvokeCommand('.form-item-model', 'addClass', ['form-disabled']));
      $response->addCommand(new InvokeCommand('#edit-model option[value=""]', 'attr', ['selected', TRUE]));
      $response->addCommand(new InvokeCommand('#edit-model', 'attr', ['disabled', TRUE]));
      $response->addCommand(new InvokeCommand('#edit-make', 'removeAttr', ['disabled']));
      $response->addCommand(new HtmlCommand('#edit-make', $options));
    }
    else {
      $options_name[] = ['_none' => '- None -'];
      $options = '<option value="">' . $this->t('2 | Make') . '</option>';
      $form['make']['#options'] = $options_name;
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('.form-item-make', 'addClass', ['form-disabled']));
      $response->addCommand(new InvokeCommand('#edit-make', 'attr', ['disabled', TRUE]));
      $response->addCommand(new InvokeCommand('.form-item-model', 'addClass', ['form-disabled']));
      $response->addCommand(new InvokeCommand('#edit-model', 'attr', ['disabled', TRUE]));
      $response->addCommand(new InvokeCommand('#edit-model option[value=""]', 'attr', ['selected', TRUE]));
      $response->addCommand(new HtmlCommand('#edit-make', $options));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function updateModel(array &$form, FormStateInterface $form_state) {
    $this->yearval = $form_state->getValue('year');
    $this->makeval = $form_state->getValue('make');
    if (!is_numeric($this->makeval)) {
      $this->makeval = 0;
    }
    // HTTP client request to get actual data.
    $make_options = [];
    $client = \Drupal::httpClient();
    $response_api = $client->request('GET', 'https://vpic.nhtsa.dot.gov/api/vehicles/GetModelsForMakeIdYear/makeId/' . $this->makeval . '/modelyear/' . $this->yearval . '?format=json');
    // To get response body content and JSON decoding it.
    $json_array_data = json_decode($response_api->getBody()->getContents());
    $count = $json_array_data->Count;
    if ($count > 0) {
      $results = $json_array_data->Results;
      if (!empty($results)) {
        foreach ($results as $result) {
          $make_options[$result->Model_Name] = $result->Model_Name;
        }
      }
      ksort($make_options);
      $options_name[] = ['_none' => '- None -'];
      $options = '<option value="">' . $this->t('3 | Model') . '</option>';
      foreach ($make_options as $value) {
        $options_name[] = [$value => $value];
        $options .= '<option value="' . $value . '">' . $value . '</option>';
      }

      $options_name = call_user_func_array('array_merge', $options_name);

      $form['model']['#options'] = $options_name;
      // Result Handling.
      $response = new AjaxResponse();
      // Populate the dropdown.
      $response->addCommand(new InvokeCommand('.form-item-model', 'removeClass', ['form-disabled']));
      $response->addCommand(new InvokeCommand('#edit-model', 'removeAttr', ['disabled']));
      $response->addCommand(new HtmlCommand('#edit-model', $options));
    }
    else {
      $options_name[] = ['_none' => '- None -'];
      $options = '<option value="">' . $this->t('3 | Model') . '</option>';
      $form['model']['#options'] = $options_name;
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('.form-item-model', 'addClass', ['form-disabled']));
      $response->addCommand(new InvokeCommand('#edit-model', 'attr', ['disabled', TRUE]));
      $response->addCommand(new HtmlCommand('#edit-model', $options));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
