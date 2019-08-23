<?php

namespace Application\Actions\Common;

use AEngine\Support\Str;
use Application\Actions\Action;
use DateTime;
use Domain\Exceptions\HttpBadRequestException;
use Domain\Exceptions\HttpNotFoundException;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Slim\Http\UploadedFile;

class FormAction extends Action
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    protected $formRepository;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    protected $formDataRepository;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    protected $fileRepository;

    /**
     * @inheritDoc
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->formRepository = $this->entityManager->getRepository(\Domain\Entities\Form::class);
        $this->formDataRepository = $this->entityManager->getRepository(\Domain\Entities\Form\Data::class);
        $this->fileRepository = $this->entityManager->getRepository(\Domain\Entities\File::class);
    }

    protected function action(): \Slim\Http\Response
    {
        /** @var \Domain\Entities\Form $item */
        $item = $this->formRepository->findOneBy([
            'address' => $this->resolveArg('unique'),
        ]);

        if ($item) {
            $remote = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? false;
            $data = $this->request->getParams();

            // CORS header sets
            foreach ($item->origin as $origin) {
                if ($remote && strpos($origin, $remote) >= 0) {
                    $this->response = $this->response->withHeader('Access-Control-Allow-Origin', $remote);
                    break;
                } else {
                    if ($origin === '*') {
                        $this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*');
                        break;
                    }
                }
            }

            if ($this->response->hasHeader('Access-Control-Allow-Origin')) {
                $this->response = $this->response->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
            }

            // mailto field prepare
            $mailto = [];
            foreach (array_map('trim', $item->mailto) as $key => $value) {
                $buf = array_map('trim', explode(':', $value));

                if (count($buf) == 2) {
                    $mailto[$buf[0]] = $buf[1];
                } else {
                    $mailto[] = $buf[0];
                }
            }

            $body = '';
            $isHtml = true;

            // mail body prepare
            if ($item->template && $item->template != '<p><br></p>') {
                $filter = new class($data) extends \AEngine\Validator\Filter
                {
                    use \AEngine\Validator\Traits\FilterRules;
                };
                $filter->addGlobalRule($filter->leadEscape());
                $filter->addGlobalRule($filter->leadTrim());
                $check = $filter->run();

                if ($check === true) {
                    $body = $this->renderer->fetchFromString($item->template, $data);
                } else {
                    throw new InvalidArgumentException('Error in POST data');
                }
            } else {
                // no template, check post data for mail body
                if ($buf = $this->request->getParam('body', false)) {
                    $body = $buf;
                } else {
                    // json in mail
                    $body = json_encode(str_escape($data), JSON_UNESCAPED_UNICODE);
                    $isHtml = false;
                }
            }

            /**
             * save request
             * @var \Domain\Entities\Form\Data $bid
             */
            $bid = new \Domain\Entities\Form\Data([
                'form_uuid' => $item->uuid,
                'message' => $body,
                'date' => new DateTime(),
            ]);
            $this->entityManager->persist($bid);

            // prepare mail attachments
            $attachments = [];
            foreach ($this->request->getUploadedFiles() as $field => $files) {
                if (!is_array($files)) $files = [$files];

                /* @var UploadedFile $file */
                foreach ($files as $file) {
                    $salt = uniqid();
                    $name = Str::translate(strtolower($file->getClientFilename()));
                    $path = UPLOAD_DIR . '/' . $salt;

                    if (!file_exists($path)) {
                        mkdir($path);
                    }

                    // create model
                    $model = new \Domain\Entities\File([
                        'name' => $name,
                        'type' => $file->getClientMediaType(),
                        'size' => (int)$file->getSize(),
                        'salt' => $salt,
                        'date' => new \DateTime(),
                        'item' => \Domain\Types\FileItemType::ITEM_FORM_DATA,
                        'item_uuid' => $bid->uuid,
                    ]);

                    $file->moveTo($path . '/' . $name);
                    $model->set('hash', sha1_file($path . '/' . $name));

                    // save model
                    $this->entityManager->persist($model);

                    // add to attachments
                    $attachments[$model->name] = $model->getInternalPath();
                }
            }

            $this->entityManager->flush();

            // send mail
            $mail = $this->send_mail([
                'subject' => $item->title,
                'to' => $mailto,
                'body' => $body,
                'isHtml' => $isHtml,
                'attachments' => $attachments,
            ]);

            if (!$mail->isError()) {
                $this->logger->info('Form sended: ' . $item->title, ['mailto' => $item->mailto]);

                if (
                    (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'xmlhttprequest') && !empty($_SERVER['HTTP_REFERER'])
                ) {
                    $this->response = $this->response->withHeader('Location', $_SERVER['HTTP_REFERER']);
                }

                return $this->respondWithData(['description' => 'Message sent',]);
            } else {
                $this->logger->warn('Form will not sended: fail', ['mailto' => $item->mailto, 'error' => $mail->ErrorInfo]);

                throw new HttpBadRequestException($this->request, 'Message not sent');
            }
        }

        throw new HttpNotFoundException($this->request);
    }
}