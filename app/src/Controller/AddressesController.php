<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;

class AddressesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Addresses');
        $this->request->allowMethod(['get', 'post', 'put', 'delete']);
        $this->viewBuilder()->setClassName('Json'); // força resposta JSON
    }

    // GET /api/addresses
    public function index()
    {
        $addresses = $this->Addresses->find()->all();
        $this->set([
            'status' => 'success',
            'data' => $addresses,
            '_serialize' => ['status', 'data']
        ]);
    }

    // GET /api/addresses/{id}
    public function view($id = null)
    {
        $addresses = $this->Addresses->findById($id)->first();
        if (!$addresses) {
            $this->set([
                'status' => 'error',
                'message' => 'Endereço não encontrado',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        $this->set([
            'status' => 'success',
            'data' => $addresses,
            '_serialize' => ['status', 'data']
        ]);
    }

    // POST /api/addresses
    public function add()
    {
        $addresses = $this->Addresses->newEmptyEntity();
        $addresses = $this->Addresses->patchEntity($addresses, $this->request->getData());

        if ($this->Addresses->save($addresses)) {
            $this->set([
                'status' => 'success',
                'data' => $addresses,
                '_serialize' => ['status', 'data']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao salvar o endereço',
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    // PUT /api/addresses/{id}
    public function edit($id = null)
    {
        $addresses = $this->Addresses->findById($id)->first();
        if (!$addresses) {
            $this->set([
                'status' => 'error',
                'message' => 'Endereço não encontrado',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        $addresses = $this->Addresses->patchEntity($addresses, $this->request->getData());
        if ($this->Addresses->save($addresses)) {
            $this->set([
                'status' => 'success',
                'data' => $addresses,
                '_serialize' => ['status', 'data']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao atualizar o endereço',
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    // DELETE /api/addresses/{id}
    public function delete($id = null)
    {
        $addresses = $this->Addresses->findById($id)->first();
        if (!$addresses) {
            $this->set([
                'status' => 'error',
                'message' => 'Endereço não encontrado',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        if ($this->Addresses->delete($addresses)) {
            $this->set([
                'status' => 'success',
                'message' => 'Endereço removido com sucesso',
                '_serialize' => ['status', 'message']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao remover o endereço',
                '_serialize' => ['status', 'message']
            ]);
        }
    }
}
