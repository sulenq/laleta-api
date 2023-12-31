<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends ResourceController
{
    protected $modelName = 'App\Models\UserModel';
    protected $format = 'json';

    public function generate_jwt($user) // return jwt token
    {
        $jwtKey = getenv('JWT_SECRET');
        $jwtAlg = getenv('JWT_ALG');
        $payload = [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'name' => $user['name'],
            'age' => $user['age'],
            'gender' => $user['gender'],
            'address' => $user['address'],
            'phone' => $user['phone'],
            'image' => $user['image'],
            // Waktu token dibuat
            'iat' => time(),
            // Waktu kedaluwarsa token (12 jam)
            'exp' => time() + 43200,
        ];

        return JWT::encode($payload, $jwtKey, $jwtAlg);
    }

    public function verifyToken() // return boolean
    {
        $authHeader = $this->request->getHeader('Authorization');
        $jwt = substr($authHeader->getValue(), 7); // Menghapus 'Bearer '
        try {
            $jwtKey = getenv('JWT_SECRET');
            $jwtAlg = getenv('JWT_ALG');
            $decoded = JWT::decode($jwt, new Key($jwtKey, $jwtAlg));
        } catch (\Exception $e) {
            $response = [
                'status' => 401,
                'message' => 'Unauthorized',
            ];
            return $this->respond($response);
        }

        if (!$decoded) {
            $response = [
                'status' => 401,
                'message' => 'Unauthorized',
            ];
            return $this->respond($response);
        }

        $response = [
            'status' => 200,
            'message' => 'Authorized',
            'payload' => (array) $decoded
        ];
        return $this->respond($response);
    }

    public function signin()
    {
        $valid = $this->validate([
            'emailOrUsername' => [
                'label' => 'Email or Username',
                'rules' => 'required|max_length[100]',
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|max_length[100]',
            ],
        ]);

        if (!$valid) {
            $response = ['status' => 400, 'invalid' => $this->validator->getErrors(), 'message' => 'Sign In data invalid'];
            return $this->respond($response);
        }

        $emailOrUsername = $this->request->getVar('emailOrUsername');
        $password = $this->request->getVar('password');

        $user = $this->model->where('email', $emailOrUsername)
            ->orWhere('username', $emailOrUsername)
            ->first();

        if (!$user) {
            $response = [
                'status' => 404,
                'message' => 'User not found'
            ];

            return $this->respond($response);
        }

        if (!password_verify($password, $user['password'])) {
            $response = [
                'status' => 400,
                'message' => 'Password is not match'
            ];

            return $this->respond($response);
        }

        $payload = [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'name' => $user['name'],
            'age' => $user['age'],
            'gender' => $user['gender'],
            'address' => $user['address'],
            'phone' => $user['phone'],
            'image' => $user['image'],
            // Waktu token dibuat
            'iat' => time(),
            // Waktu kedaluwarsa token (12 jam)
            'exp' => time() + 43200,
        ];
        $jwt = $this->generate_jwt($user);
        $response = [
            'status' => 200,
            'message' => 'Signed In',
            'jwt' => $jwt,
            'payload' => $payload
        ];

        return $this->respond($response);
    }
}