<?php declare(strict_types=1);

namespace App\Application\Actions\Cup\Form;

class FormUpdateAction extends FormAction
{
    protected function action(): \Slim\Http\Response
    {
        if ($this->resolveArg('uuid') && \Ramsey\Uuid\Uuid::isValid($this->resolveArg('uuid'))) {
            /** @var \App\Domain\Entities\Form $item */
            $item = $this->formRepository->findOneBy(['uuid' => $this->resolveArg('uuid')]);

            if (!$item->isEmpty()) {
                if ($this->request->isPost()) {
                    $data = [
                        'uuid' => $item->uuid,
                        'title' => $this->request->getParam('title'),
                        'address' => $this->request->getParam('address'),
                        'template' => $this->request->getParam('template'),
                        'save_data' => $this->request->getParam('save_data'),
                        'recaptcha' => $this->request->getParam('recaptcha'),
                        'origin' => $this->request->getParam('origin'),
                        'mailto' => $this->request->getParam('mailto'),
                    ];

                    $check = \App\Domain\Filters\Form::check($data);

                    if ($check === true) {
                        $item->replace($data);
                        $this->entityManager->persist($item);
                        $this->entityManager->flush();

                        if ($this->request->getParam('save', 'exit') === 'exit') {
                            return $this->response->withAddedHeader('Location', '/cup/form')->withStatus(301);
                        }

                        return $this->response->withAddedHeader('Location', $this->request->getUri()->getPath())->withStatus(301);
                    }
                    $this->addErrorFromCheck($check);
                }

                return $this->respondWithTemplate('cup/form/form.twig', ['item' => $item]);
            }
        }

        return $this->response->withAddedHeader('Location', '/cup/form')->withStatus(301);
    }
}
