<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    public function redirect()
    {
        // 사용자를 Google 인증 화면으로 보냅니다.
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        // Google이 redirect_uri로 돌려보낸 콜백을 처리합니다.
        // 이 시점에 access token 교환이 일어나고, Google 프로필 정보를 받습니다.
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            // 로컬 개발 환경에서는 호스트/포트(127.0.0.1 vs localhost) 혼용이나 쿠키 설정으로 인해
            // state 검증이 실패하는 경우가 있습니다. 로컬에서는 stateless로 우회해 개발을 계속할 수 있게 합니다.
            // 운영 환경에서는 state 검증이 보안상 중요하므로 stateless를 사용하지 않습니다.
            if (!app()->environment('local')) {
                throw $e;
            }

            $googleUser = Socialite::driver('google')->stateless()->user();
        }

        // 기존 계정이 있으면 재사용합니다.
        // - google_id가 이미 저장돼 있다면 그걸 우선
        // - google_id가 없더라도 email이 같으면 같은 사용자로 취급(기존 계정 연동)
        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'User'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                // Google 로그인만으로도 계정이 생성될 수 있어 랜덤 비밀번호를 넣어둡니다.
                // (일반 이메일/비밀번호 로그인은 지금 단계에서는 제공하지 않음)
                'password' => Str::password(48),
            ]);
        } else {
            // 기존 계정이면, google_id(없을 때만)와 avatar만 업데이트합니다.
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ])->save();
        }

        // 세션 기반 로그인 처리(Laravel auth)
        Auth::login($user, true);

        return redirect()->route('dashboard');
    }
}
