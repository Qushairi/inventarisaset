<?php

namespace App\Http\Controllers;

/**
 * Controller dasar yang menjadi induk bagi seluruh controller aplikasi.
 *
 * Kelas ini sengaja tidak memiliki implementasi agar fitur bersama dapat
 * ditambahkan di satu tempat dan diwariskan oleh semua controller turunan.
 */
abstract class Controller
{
    // Seluruh controller aplikasi mewarisi kelas dasar ini.
}
