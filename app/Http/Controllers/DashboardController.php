<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $role = (int) $user->role;

        $stats = match (true) {
            $role === 3 => $this->adminStats(),           // ADMIN
            $role === 2 => $this->agentStats($user),      // AGENT
            $role === 4 => $this->ownerStats($user),      // OWNER
            default     => $this->userStats($user),       // USER (1)
        };

        return response()->json(['data' => $stats]);
    }

    // ── ADMIN ────────────────────────────────────────────────────────────────

    private function adminStats(): array
    {
        return [
            'totalUsers'       => User::whereNull('deleted_at')->count(),
            'totalProperties'  => Property::whereNull('deleted_at')->count(),
            'activeListings'   => Property::whereNull('deleted_at')->where('status', 'active')->count(),
            'pendingApprovals' => Property::whereNull('deleted_at')->where('status', 'pending')->count(),
            'propertyViews'    => Property::whereNull('deleted_at')->sum('views') ?? 0,
            'totalInquiries'   => $this->inquiryCount(),
            'inquiriesSent'    => $this->inquiryCount(),
            'upcomingViewings' => $this->upcomingViewings(),
            'revenue'          => $this->totalRevenue(),
        ];
    }

    // ── AGENT ────────────────────────────────────────────────────────────────

    private function agentStats(User $user): array
    {
        $base = Property::whereNull('deleted_at')->where('user_id', $user->id);

        return [
            'totalProperties' => (clone $base)->count(),
            'activeListings'  => (clone $base)->where('status', 'active')->count(),
            'propertyViews'   => (clone $base)->sum('views') ?? 0,
            'revenue'         => (clone $base)->where('status', 'sold')->sum('price') ?? 0,
        ];
    }

    // ── OWNER ────────────────────────────────────────────────────────────────

    private function ownerStats(User $user): array
    {
        $base = Property::whereNull('deleted_at')->where('user_id', $user->id);

        return [
            'totalProperties'  => (clone $base)->count(),
            'activeListings'   => (clone $base)->where('status', 'active')->count(),
            'savedProperties'  => $this->savedProperties($user),
            'totalInquiries'   => $this->userInquiryCount($user),
            'propertyViews'    => (clone $base)->sum('views') ?? 0,
            'revenue'          => (clone $base)->where('status', 'sold')->sum('price') ?? 0,
        ];
    }

    // ── USER ─────────────────────────────────────────────────────────────────

    private function userStats(User $user): array
    {
        return [
            'savedProperties' => $this->savedProperties($user),
            'propertyViews'   => 0, // personalised views if you track them per-user
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function savedProperties(User $user): int
    {
        // Adjust to your saved/favourites relationship name
        if (method_exists($user, 'savedProperties')) {
            return $user->savedProperties()->count();
        }
        return 0;
    }

    private function inquiryCount(): int
    {
        // Adjust to your Inquiry model name if it exists
        if (class_exists(\App\Models\Inquiry::class)) {
            return \App\Models\Inquiry::count();
        }
        return 0;
    }

    private function userInquiryCount(User $user): int
    {
        if (class_exists(\App\Models\Inquiry::class)) {
            return \App\Models\Inquiry::where('user_id', $user->id)->count();
        }
        return 0;
    }

    private function upcomingViewings(): int
    {
        // Adjust to your Viewing/Appointment model if it exists
        if (class_exists(\App\Models\Viewing::class)) {
            return \App\Models\Viewing::where('scheduled_at', '>=', now())->count();
        }
        return 0;
    }

    private function totalRevenue(): float|int
    {
        // Sum price of all sold properties — adjust to match your revenue logic
        return Property::whereNull('deleted_at')
            ->where('status', 'sold')
            ->sum('price') ?? 0;
    }
}