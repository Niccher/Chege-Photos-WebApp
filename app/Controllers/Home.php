<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/photos');
        }
        return view('public/home');
    }

    public function about()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/photos');
        }
        $data = [
            'faceModelPack' => setting('ML.faceModelPack') ?? 'buffalo_l'
        ];
        return view('public/about', $data);
    }

    public function android()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/photos');
        }
        return view('public/android');
    }

    public function ml()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/photos');
        }
        $data = [
            'faceModelPack' => setting('ML.faceModelPack') ?? 'buffalo_l'
        ];
        return view('public/ml', $data);
    }

    public function setup()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/photos');
        }
        return view('public/setup');
    }

    public function faq()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/photos');
        }
        return view('public/faq');
    }
}
