<?php declare(strict_types=1);

namespace App\Application\Actions\Cup\User;

use App\Domain\Service\User\Exception\EmailAlreadyExistsException;
use App\Domain\Service\User\Exception\EmailBannedException;
use App\Domain\Service\User\Exception\MissingUniqueValueException;
use App\Domain\Service\User\Exception\PhoneAlreadyExistsException;
use App\Domain\Service\User\Exception\UsernameAlreadyExistsException;
use App\Domain\Service\User\Exception\WrongEmailValueException;
use App\Domain\Service\User\Exception\WrongPasswordException;
use App\Domain\Service\User\Exception\WrongPhoneValueException;
use App\Domain\Service\User\Exception\WrongUsernameValueException;

class UserCreateAction extends UserAction
{
    protected function action(): \Slim\Psr7\Response
    {
        $userGroups = $this->userGroupService->read();

        if ($this->isPost()) {
            try {
                $user = $this->userService->create([
                    'username' => $this->getParam('username'),
                    'firstname' => $this->getParam('firstname'),
                    'lastname' => $this->getParam('lastname'),
                    'patronymic' => $this->getParam('patronymic'),
                    'gender' => $this->getParam('gender'),
                    'birthdate' => $this->getParam('birthdate'),
                    'country' => $this->getParam('country'),
                    'city' => $this->getParam('city'),
                    'address' => $this->getParam('address'),
                    'postcode' => $this->getParam('postcode'),
                    'additional' => $this->getParam('additional'),
                    'email' => $this->getParam('email'),
                    'is_allow_mail' => $this->getParam('is_allow_mail'),
                    'phone' => $this->getParam('phone'),
                    'password' => $this->getParam('password'),
                    'company' => $this->getParam('company'),
                    'legal' => $this->getParam('legal'),
                    'website' => $this->getParam('website'),
                    'source' => $this->getParam('source'),
                    'group_uuid' => $this->getParam('group_uuid'),
                    'language' => $this->getParam('language'),
                    'external_id' => $this->getParam('external_id'),
                ]);
                $user = $this->processEntityFiles($user);

                $this->container->get(\App\Application\PubSub::class)->publish('cup:user:create', $user);

                switch (true) {
                    case $this->getParam('save', 'exit') === 'exit':
                        return $this->respondWithRedirect('/cup/user');

                    default:
                        return $this->respondWithRedirect('/cup/user/' . $user->uuid . '/edit');
                }
            } catch (MissingUniqueValueException $e) {
                $this->addError('username', $e->getMessage());
                $this->addError('email', $e->getMessage());
                $this->addError('phone', $e->getMessage());
            } catch (WrongPasswordException $e) {
                $this->addError('password', $e->getMessage());
            } catch (UsernameAlreadyExistsException|WrongUsernameValueException $e) {
                $this->addError('username', $e->getMessage());
            } catch (EmailAlreadyExistsException|EmailBannedException|WrongEmailValueException $e) {
                $this->addError('email', $e->getMessage());
            } catch (PhoneAlreadyExistsException|WrongPhoneValueException $e) {
                $this->addError('phone', $e->getMessage());
            }
        }

        return $this->respondWithTemplate('cup/user/form.twig', [
            'groups' => $userGroups,
        ]);
    }
}
