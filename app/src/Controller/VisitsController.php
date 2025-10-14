<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;

class VisitsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Visits');
        $this->request->allowMethod(['get', 'post', 'put', 'delete']);
        $this->viewBuilder()->setClassName('Json'); // força resposta JSON
    }

    // GET /api/visits
    public function index()
    {
        $visits = $this->Visits->find()->all();
        $this->set([
            'status' => 'success',
            'data' => $visits,
            '_serialize' => ['status', 'data']
        ]);
    }

    // GET /api/visits/{id}
    public function view($id = null)
    {
        $visit = $this->Visits->findById($id)->first();
        if (!$visit) {
            $this->set([
                'status' => 'error',
                'message' => 'Visita não encontrada',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        $this->set([
            'status' => 'success',
            'data' => $visit,
            '_serialize' => ['status', 'data']
        ]);
    }

    // POST /api/visits
    public function add()
    {
        $visit = $this->Visits->newEmptyEntity();
        $visit = $this->Visits->patchEntity($visit, $this->request->getData());

        if ($this->Visits->save($visit)) {
            $this->set([
                'status' => 'success',
                'data' => $visit,
                '_serialize' => ['status', 'data']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao salvar a visita',
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    // PUT /api/visits/{id}
    public function edit($id = null)
    {
        $visit = $this->Visits->findById($id)->first();
        if (!$visit) {
            $this->set([
                'status' => 'error',
                'message' => 'Visita não encontrada',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        $visit = $this->Visits->patchEntity($visit, $this->request->getData());
        if ($this->Visits->save($visit)) {
            $this->set([
                'status' => 'success',
                'data' => $visit,
                '_serialize' => ['status', 'data']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao atualizar a visita',
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    // DELETE /api/visits/{id}
    public function delete($id = null)
    {
        $visit = $this->Visits->findById($id)->first();
        if (!$visit) {
            $this->set([
                'status' => 'error',
                'message' => 'Visita não encontrada',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        if ($this->Visits->delete($visit)) {
            $this->set([
                'status' => 'success',
                'message' => 'Visita removida com sucesso',
                '_serialize' => ['status', 'message']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao remover a visita',
                '_serialize' => ['status', 'message']
            ]);
        }
    }
}
