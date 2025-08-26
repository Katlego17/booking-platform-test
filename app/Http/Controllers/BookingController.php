<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Client;
use Carbon\Carbon;
use App\Http\Resources\BookingResource;
use Illuminate\Support\Facades\Auth;
use App\Actions\CheckBookingOverlap;

class BookingController extends Controller
{
    /**
     * Renders the bookings table
     */
    public function index()
    {
        $bookings = Booking::orderBy('created_at', 'desc')->where('user_id', Auth::id())->paginate(10);
        $clients = Client::orderBy('name','asc')->get();

        return view('bookings', compact(['bookings','clients']));
    }

    /**
     * Function for the end-point that will get bookings by weeks
     * In this format '2025-08-05'
     */
    public function getBookingsByWeeks(Request $request)
    {
        $weekDate = $request->query('week');

        try {
            $date = Carbon::parse($weekDate);

            // Get Monday and Sunday of that week
            $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek   = $date->copy()->endOfWeek(Carbon::SUNDAY);

            // Filter bookings within the week range (using start_time)
            $bookings = Booking::where('user_id', Auth::id())
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->orderBy('start_time')
                ->with(['user', 'client'])
                ->paginate(10);

            return BookingResource::collection($bookings);
        }
        catch (\Exception $e)
        {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Stores a new booking and also checks if there are any overlapping dates
     */
    public function store(Request $request)
    {
        try
        {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'client_id' => 'required|exists:clients,id',
            ]);

            $userId = auth()->id();

            if (CheckBookingOverlap::handle($validated))
            {
                return back()->withInput()->withErrors(['overlap' => 'Overlapping booking exists']);
            }

            $booking = Booking::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'client_id' => $validated['client_id'],
                'user_id' => $userId,
            ]);

            return redirect()->route('bookings.index')->with('success', 'Booking created successfully!');
        }
        catch (\Illuminate\Validation\ValidationException $e)
        {
            throw $e;
        }
        catch (\Exception $e)
        {
            return back()->withInput()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Deletes a booking
     */
    public function destroy($id)
    {
        try
        {
            $booking = Booking::findOrFail($id);
            $booking->delete();

            return redirect()
                ->back()
                ->with('success', 'Booking deleted successfully.');
        }
        catch (\Exception $e)
        {
            return redirect()
                ->back()
                ->with('error', 'An error occurred while deleting the booking.');
        }
    }

    /**
     * Updates a booking
     */
    public function update(Request $request, $id)
    {
        try
        {
            $validated = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'title' => 'required|string|max:255',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'description' => 'nullable|string',
            ]);

            $booking = Booking::findOrFail($id);

            if (CheckBookingOverlap::handle($validated, $booking->id))
            {
                return back()->withInput()->withErrors(['overlap' => 'Overlapping booking exists']);
            }

            $booking->update($request->all());

            return redirect()->back()->with('cleared', true)->with('success', 'Booking updated successfully.');
        }
        catch (\Exception $e)
        {
            return redirect()
                ->back()
                ->with('error', 'An error occurred while updating the book.');
        }
    }
}
