<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GitController extends Controller
{
    public function UpdateGit(Request $request) {

        $secret_key_user = $request->input('secret_key');
        $secret_key_env = env('SECRET_KEY');

        if (Cache::has('lock')) {
            return response()->json(['error' => 'Another request is in progress'], 409);
        }

        if ($secret_key_user === $secret_key_env) {
            $lock = Cache::lock('update in process', 300);
            if ($lock->get()) {
                try {
                    Log::info('Дата: ' . now() . ' IP:' . $request->ip());
                    Log::info('Branch checkout:');
                    $output = shell_exec('git checkout main');
                    Log::info($output);
                    Log::info('Fetch All:');
                    $output = shell_exec('git fetch --all');
                    Log::info($output);
                    Log::info('Reset Hard:');
                    $output = shell_exec('git reset --hard origin/main');
                    Log::info($output);
                    Log::info('Pull origin main:');
                    $output = shell_exec('git pull origin main');
                    Log::info($output);
                    $lock->release();
                    return response()->json(['message' => 'Code updated'], 200);
                }
                catch (\Exception $e) {
                    Log::error('Error: ' . $e->getMessage());
                    $lock->release();
                    return response()->json($e->getMessage(), 500);
                }
            }
        }
        else {
            return response()->json(['error' => 'Incorrect secret key'], 401);
        }
    }
}
