<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Bookings') }}
            </h2>
            @if (session('success'))
                <div style="color:green; margin-bottom:10px;">
                    {{ session('success') }}
                </div>
            @endif
            <!-- Add Booking Button -->
            <button id="openBookingModal" class="bg-green-600 hover:bg-green-800 text-white font-semibold py-2 px-4 rounded">
                Add Booking
            </button>
        </div>
    </x-slot>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <h3 class="text-lg font-semibold mb-4">New Booking</h3>

            @if ($errors->any())
                <div style="color:red; margin-bottom:10px;">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('booking.store') }}" method="POST" id="bookingForm">
                @csrf
                <div class="mb-3">
                    <label class="block text-gray-700">Select Client</label>
                    <select name="client_id" class="w-full border rounded px-3 py-2" required>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <div style="color: red;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">Title</label>
                    <input type="text" name="title" class="w-full border rounded px-3 py-2" value="{{ old('title') }}" required>
                    @error('title')
                        <div style="color: red;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">Start Time</label>
                    <input type="datetime-local" name="start_time" class="w-full border rounded px-3 py-2" value="{{ old('start_time') }}" required>
                    @error('start_time')
                        <div style="color: red;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">End Time</label>
                    <input type="datetime-local" name="end_time" class="w-full border rounded px-3 py-2" value="{{ old('end_time') }}" required>
                    @error('end_time')
                        <div style="color: red;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">Description</label>
                    <textarea name="description" class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color: red;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" id="closeBookingModal" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
                </div>
            </form>

        </div>
    </div>

    <!-- Edit Booking Modal -->
    <div id="editBookingModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <h3 class="text-lg font-semibold mb-4">Edit Booking</h3>

            <!-- Validation Errors -->
            @if ($errors->any() && session('editing'))
                <div style="color:red; margin-bottom:10px;">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="editBookingForm" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="block text-gray-700">Select Client</label>
                    <select name="client_id" id="edit_client_id" class="w-full border rounded px-3 py-2" required>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">Title</label>
                    <input type="text" name="title" id="edit_title" class="w-full border rounded px-3 py-2" required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">Start Time</label>
                    <input type="datetime-local" name="start_time" id="edit_start_time" class="w-full border rounded px-3 py-2" required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">End Time</label>
                    <input type="datetime-local" name="end_time" id="edit_end_time" class="w-full border rounded px-3 py-2" required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700">Description</label>
                    <textarea name="description" id="edit_description" class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" id="closeEditBookingModal" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table style="width:100%; border-collapse:collapse; font-family:Arial, sans-serif; font-size:14px;">
                        <thead>
                            <tr style="background-color:#f4f4f4; border:1px solid #ddd;">
                                <th style="border:1px solid #ddd; padding:8px; text-align:centre;">Client Name</th>
                                <th style="border:1px solid #ddd; padding:8px; text-align:centre;">Title</th>
                                <th style="border:1px solid #ddd; padding:8px; text-align:centre;">Start Time</th>
                                <th style="border:1px solid #ddd; padding:8px; text-align:centre;">End Time</th>
                                <th style="border:1px solid #ddd; padding:8px; text-align:centre;">Date Added</th>
                                <th style="border:1px solid #ddd; padding:8px; text-align:centre;">Description</th>
                                <th style="border:1px solid #ddd; padding:8px; text-align:centre;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr style="border:1px solid #ddd;">
                                    <td style="border:1px solid #ddd; padding:7px;">{{ $booking->client->name ?? 'N/A' }}</td>
                                    <td style="border:1px solid #ddd; padding:7px;">{{ $booking->title ?? 'N/A' }}</td>
                                    <td style="border:1px solid #ddd; padding:7px;">{{ $booking->start_time->format('F j, Y, g:i a') ?? 'N/A' }}</td>
                                    <td style="border:1px solid #ddd; padding:7px;">{{ $booking->end_time->format('F j, Y, g:i a') ?? 'Pending' }}</td>
                                    <td style="border:1px solid #ddd; padding:7px;">{{ $booking->created_at->format('F j, Y, g:i a')}}</td>
                                    <td style="border:1px solid #ddd; padding:7px;">{{ $booking->description ?? 'N/A' }}</td>
                                    <td style="border:1px solid #ddd; padding:7px; width:100%; display:flex; justify-content:space-between; gap:5px;">

                                        <!-- Edit Button -->
                                        <button type="button"
                                                class="openEditBookingModal"
                                                data-id="{{ $booking->id }}"
                                                data-client="{{ $booking->client_id }}"
                                                data-title="{{ $booking->title }}"
                                                data-start="{{ $booking->start_time->format('Y-m-d\TH:i') }}"
                                                data-end="{{ $booking->end_time->format('Y-m-d\TH:i') }}"
                                                data-description="{{ $booking->description }}"
                                                style="flex:1; text-align:center; padding:6px 10px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px; font-size:12px;">
                                            Edit
                                        </button>

                                        <form action="{{ route('booking.deletion', $booking->id) }}" method="POST" style="flex:1; display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="width:100%; padding:6px 10px; background:#dc3545; color:#fff; border:none; border-radius:4px; font-size:12px; cursor:pointer;"
                                                    onclick="return confirm('Delete this booking?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:15px; border:1px solid #ddd; color:#666;">
                                        No bookings found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div style="margin-top: 15px; text-align: center; color: white;">
                        {{ $bookings->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script>
        const modal = document.getElementById('bookingModal');
        const openBtn = document.getElementById('openBookingModal');
        const closeBtn = document.getElementById('closeBookingModal');

        openBtn.addEventListener('click', () => {
            modal.classList.remove('hidden');
        });

        closeBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Close modal if clicking outside content
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });

        const editModal = document.getElementById('editBookingModal');
        const closeEditBtn = document.getElementById('closeEditBookingModal');
        const editForm = document.getElementById('editBookingForm');

        document.querySelectorAll('.openEditBookingModal').forEach(btn => {
            btn.addEventListener('click', function () {
                // Fill form with booking data
                editForm.action = `/bookings/${this.dataset.id}`;
                document.getElementById('edit_client_id').value = this.dataset.client;
                document.getElementById('edit_title').value = this.dataset.title;
                document.getElementById('edit_start_time').value = this.dataset.start;
                document.getElementById('edit_end_time').value = this.dataset.end;
                document.getElementById('edit_description').value = this.dataset.description;

                // Show modal
                editModal.classList.remove('hidden');
            });
        });

        closeEditBtn.addEventListener('click', () => {
            editModal.classList.add('hidden');
        });

        // Close modal if clicking outside content
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) {
                editModal.classList.add('hidden');
            }
        });

        @if ($errors->any())
            modal.classList.remove('hidden');
        @endif

    </script>

</x-app-layout>
