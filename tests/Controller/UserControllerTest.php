<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Tests\AbstractTest;
use App\Entity\User;


class UserControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    public function testUserAuth(): void
    {
        $client = static::getClient();
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.encoder');
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'krasikov@gmail.com',
            'password' => 'zxc12345',
        ]);
        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $decodeResponse = $jwtManager->decode($responseData['token']);
        $this->assertEquals('krasikov@gmail.com', $decodeResponse['username']);
    }
    

    public function testUserAuthFailed(): void
    {
        // При пустом значение username
        $client = static::getClient();
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => '',
            'password' => '',
        ]);        
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'The key "username" must be a non-empty string.',
        );

        // При пустом значение password
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'krasikov@gmail.com',
            'password' => '',
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'The key "password" must be a non-empty string.',
        );

        // При не верных почте и паролю
        $client = static::getClient();
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'kirov@gmail.com',
            'password' => '123456',
        ]);
        $this->assertResponseStatusCodeSame(401);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'Invalid credentials.',
        );

        // При не верном пароле
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'krasikov@gmail.com',
            'password' => '123456',
        ]);
        $this->assertResponseStatusCodeSame(401);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'Invalid credentials.',
        );
    }

    public function testUserRegister(): void
    {
        $client = static::getClient();
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.encoder');
        $client->jsonRequest('POST', '/api/v1/register', [
            'username' => 'petrov@gmail.com',
            'password' => '123456',
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $decodeResponse = $jwtManager->decode($responseData['token']);
        $this->assertEquals('petrov@gmail.com', $decodeResponse['username']);
    }

    public function testUserRegisterFailed(): void
    {
        $client = static::getClient();
        $client->jsonRequest('POST', '/api/v1/register', [
            'username' => '',
            'password' => '',
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'Поле email обязательно, Поле пароль обязательно, Пароль должен содержать 6 символов или больше',
        );

        $client->jsonRequest('POST', '/api/v1/register', [
            'username' => 'kirov@gmail.com',
            'password' => '',
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'Поле пароль обязательно, Пароль должен содержать 6 символов или больше',
        );

        $client->jsonRequest('POST', '/api/v1/register', [
            'username' => 'kirov@gmail.com',
            'password' => '123',
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'Пароль должен содержать 6 символов или больше',
        );

        $client->jsonRequest('POST', '/api/v1/register', [
            'username' => 'krasikov@gmail.com',
            'password' => '123456',
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'Пользователь с таким email уже сущесвует',
        );
    }

    public function testUserCurrent(): void
    {
        $client = static::getClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'krasikov@gmail.com',
            'password' => 'zxc12345',
        ]);
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $dataAuth = json_decode($response->getContent(), true);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $dataAuth['token']));

        $client->request('GET', '/api/v1/users/current');
        $this->assertResponseIsSuccessful();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'krasikov@gmail.com']);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($user->getEmail(), $responseData['username']);
        $this->assertEquals($user->getBalance(), $responseData['balance']);
        $this->assertEquals($user->getRoles(), $responseData['roles']);
    }
    
    public function testUserCurrentFailed(): void
    {
        $client = static::getClient();
        $client->request('GET', '/api/v1/users/current');
        $this->assertResponseStatusCodeSame(401);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'JWT Token not found',
        );

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', 'eyJ0eXAiOiJKV1QiLCJhbGciO'));
        $client->request('GET', '/api/v1/users/current');
        $this->assertResponseStatusCodeSame(401);
        $this->assertEquals(
            json_decode($client->getResponse()->getContent(), true)['message'],
            'Invalid JWT Token',
        );
    }
}
