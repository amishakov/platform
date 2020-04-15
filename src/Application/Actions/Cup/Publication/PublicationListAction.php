<?php declare(strict_types=1);

namespace App\Application\Actions\Cup\Publication;

class PublicationListAction extends PublicationAction
{
    protected function action(): \Slim\Http\Response
    {
        $categories = collect($this->categoryRepository->findAll());
        $publications = collect($this->publicationRepository->findAll());

        return $this->respondWithTemplate('cup/publication/index.twig', [
            'categories' => $categories,
            'publications' => $publications,
        ]);
    }
}
