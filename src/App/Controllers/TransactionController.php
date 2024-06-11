<?php

declare(strict_types=1);

namespace App\Controllers;

use Framework\TemplateEngine;
use App\Services\{ValidatorService,TransactionService};

class TransactionController
{
  public function __construct(
    private TemplateEngine $view,
    private ValidatorService $validatorService,
    private TransactionService $transactionService
  ) {
  }

  public function createView()
  {
    echo $this->view->render("transactions/create.php");
  }

  public function create() {
    $this->validatorService->validateTransaction($_POST);
    $this->transactionService->create($_POST);

    redirectTo('/');
  }

  public function editView(array $params) //the $params comes from the key-value pair created for the route and route parameter in the Router.php
  {
    $transaction = $this->transactionService->getUserTransaction(
      $params['transaction']
    );

    if(!$transaction) {
      redirectTo('/');
    }

    echo $this->view->render('transactions/edit.php', [
      'transaction' => $transaction // the $transaction is passed to prefill the form with values
    ]);
  }

  public function edit(array $params) {
    $transaction = $this->transactionService->getUserTransaction(
      $params['transaction']
    );

    if(!$transaction) {
      redirectTo('/');
    }

    $this->validatorService->validateTransaction($_POST);

    $this->transactionService->update($_POST, $transaction['id']);

    redirectTo($_SERVER['HTTP_REFERER']); // to go back to the page that is being edited
  }

  public function delete(array $params)
  {
    $this->transactionService->delete((int) $params['transaction']);

    redirectTo('/');
  }
}