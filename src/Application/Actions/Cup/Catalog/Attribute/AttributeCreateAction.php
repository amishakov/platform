<?php declare(strict_types=1);

namespace App\Application\Actions\Cup\Catalog\Attribute;

use App\Application\Actions\Cup\Catalog\CatalogAction;
use App\Domain\Service\Catalog\Exception\MissingTitleValueException;
use App\Domain\Service\Catalog\Exception\TitleAlreadyExistsException;
use App\Domain\Service\Catalog\Exception\WrongTitleValueException;

class AttributeCreateAction extends CatalogAction
{
    protected function action(): \Slim\Psr7\Response
    {
        if ($this->isPost()) {
            try {
                $attribute = $this->catalogAttributeService->create([
                    'title' => $this->getParam('title'),
                    'address' => $this->getParam('address'),
                    'type' => $this->getParam('type'),
                    'group' => $this->getParam('group'),
                    'is_filter' => $this->getParam('is_filter'),
                ]);

                $this->container->get(\App\Application\PubSub::class)->publish('cup:catalog:attribute:create', $attribute);

                switch (true) {
                    case $this->getParam('save', 'exit') === 'exit':
                        return $this->respondWithRedirect('/cup/catalog/attribute');

                    default:
                        return $this->respondWithRedirect('/cup/catalog/attribute/' . $attribute->uuid . '/edit');
                }
            } catch (MissingTitleValueException|TitleAlreadyExistsException|WrongTitleValueException $e) {
                $this->addError('title', $e->getMessage());
            }
        }

        return $this->respondWithTemplate('cup/catalog/attribute/form.twig', [
            'groups' => $this->db->table('catalog_attribute')->select('group')->distinct()->get()->pluck('group'),
        ]);
    }
}
