<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;

class WorkdaysController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Workdays');
        $this->request->allowMethod(['get', 'post', 'put', 'delete']);
        $this->viewBuilder()->setClassName('Json'); // força resposta JSON
    }

    // GET /api/workdays
    public function index()
    {
        $workdays = $this->Workdays->find()->all();
        $this->set([
            'status' => 'success',
            'data' => $workdays,
            '_serialize' => ['status', 'data']
        ]);
    }

    // GET /api/workdays/{id}
    public function view($id = null)
    {
        $workday = $this->Workdays->findById($id)->first();
        if (!$workday) {
            $this->set([
                'status' => 'error',
                'message' => 'Dia de trabalho não encontrado',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        $this->set([
            'status' => 'success',
            'data' => $workday,
            '_serialize' => ['status', 'data']
        ]);
    }

    // POST /api/workdays
    public function add()
    {
        $workday = $this->Workdays->newEmptyEntity();
        $workday = $this->Workdays->patchEntity($workday, $this->request->getData());

        if ($this->Workdays->save($workday)) {
            $this->set([
                'status' => 'success',
                'data' => $workday,
                '_serialize' => ['status', 'data']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao salvar o dia de trabalho',
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    // PUT /api/workdays/{id}
    public function edit($id = null)
    {
        $workday = $this->Workdays->findById($id)->first();
        if (!$workday) {
            $this->set([
                'status' => 'error',
                'message' => 'Dia de trabalho não encontrado',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        $workday = $this->Workdays->patchEntity($workday, $this->request->getData());
        if ($this->Workdays->save($workday)) {
            $this->set([
                'status' => 'success',
                'data' => $workday,
                '_serialize' => ['status', 'data']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao atualizar o dia de trabalho',
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    // DELETE /api/workdays/{id}
    public function delete($id = null)
    {
        $workday = $this->Workdays->findById($id)->first();
        if (!$workday) {
            $this->set([
                'status' => 'error',
                'message' => 'Dia de trabalho não encontrado',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        if ($this->Workdays->delete($workday)) {
            $this->set([
                'status' => 'success',
                'message' => 'Dia de trabalho removido com sucesso',
                '_serialize' => ['status', 'message']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao remover o dia de trabalho',
                '_serialize' => ['status', 'message']
            ]);
        }
    }
}
