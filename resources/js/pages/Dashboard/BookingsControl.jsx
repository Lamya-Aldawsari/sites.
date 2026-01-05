import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { format } from 'date-fns';

export default function BookingsControl() {
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('pending');

    useEffect(() => {
        fetchBookings();
    }, [filter]);

    const fetchBookings = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/dashboard/bookings', {
                params: { status: filter },
                headers: { Authorization: `Bearer ${token}` },
            });
            setBookings(response.data.data || []);
        } catch (error) {
            console.error('Error fetching bookings:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleAccept = async (bookingId) => {
        try {
            const token = localStorage.getItem('token');
            await axios.post(`/api/bookings/${bookingId}/accept`, {}, {
                headers: { Authorization: `Bearer ${token}` },
            });
            fetchBookings();
        } catch (error) {
            alert('Failed to accept booking');
        }
    };

    const handleReject = async (bookingId) => {
        const reason = prompt('Reason for rejection (optional):');
        try {
            const token = localStorage.getItem('token');
            await axios.post(`/api/bookings/${bookingId}/reject`, {
                reason: reason || null,
            }, {
                headers: { Authorization: `Bearer ${token}` },
            });
            fetchBookings();
        } catch (error) {
            alert('Failed to reject booking');
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading bookings...</div>;
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Booking Control</h1>
                <p className="mt-2 text-gray-600">Manage incoming booking requests</p>
            </div>

            <div className="flex space-x-2 mb-6">
                {['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'].map((status) => (
                    <button
                        key={status}
                        onClick={() => setFilter(status)}
                        className={`px-4 py-2 rounded-md text-sm font-medium ${
                            filter === status
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50'
                        }`}
                    >
                        {status.charAt(0).toUpperCase() + status.slice(1)}
                    </button>
                ))}
            </div>

            <div className="space-y-4">
                {bookings.map((booking) => (
                    <div key={booking.id} className="bg-white rounded-lg shadow p-4 sm:p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div className="flex-1">
                                <div className="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 className="text-lg font-semibold">{booking.customer?.name}</h3>
                                        <p className="text-sm text-gray-600">{booking.boat?.name}</p>
                                    </div>
                                    <span className={`px-2 py-1 rounded text-xs ${
                                        booking.booking_mode === 'on_demand' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'
                                    }`}>
                                        {booking.booking_mode === 'on_demand' ? 'On-Demand' : 'Scheduled'}
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 gap-4 text-sm text-gray-600 mt-4">
                                    <div>
                                        <p className="font-medium">Start Time</p>
                                        <p>{format(new Date(booking.start_time), 'MMM dd, yyyy hh:mm a')}</p>
                                    </div>
                                    <div>
                                        <p className="font-medium">End Time</p>
                                        <p>{format(new Date(booking.end_time), 'MMM dd, yyyy hh:mm a')}</p>
                                    </div>
                                    <div>
                                        <p className="font-medium">Duration</p>
                                        <p>{booking.duration} hours</p>
                                    </div>
                                    <div>
                                        <p className="font-medium">Total Amount</p>
                                        <p className="font-semibold text-green-600">${booking.total_amount}</p>
                                    </div>
                                </div>
                                {booking.payment_hold && (
                                    <div className="mt-2 text-xs text-yellow-600">
                                        Payment Hold: ${booking.payment_hold.amount} (Expires: {format(new Date(booking.payment_hold.hold_expires_at), 'MMM dd, yyyy')})
                                    </div>
                                )}
                            </div>
                            {booking.status === 'pending' && (
                                <div className="flex space-x-2">
                                    <button
                                        onClick={() => handleAccept(booking.id)}
                                        className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm"
                                    >
                                        Accept
                                    </button>
                                    <button
                                        onClick={() => handleReject(booking.id)}
                                        className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm"
                                    >
                                        Reject
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {bookings.length === 0 && (
                <div className="text-center py-12 bg-white rounded-lg shadow">
                    <p className="text-gray-500">No {filter} bookings found</p>
                </div>
            )}
        </div>
    );
}

