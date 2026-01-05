import React, { useState, useEffect } from 'react';
import Calendar from 'react-calendar';
import 'react-calendar/dist/Calendar.css';
import axios from 'axios';
import { format } from 'date-fns';

export default function CalendarView() {
    const [selectedDate, setSelectedDate] = useState(new Date());
    const [selectedBoat, setSelectedBoat] = useState(null);
    const [boats, setBoats] = useState([]);
    const [availability, setAvailability] = useState({});
    const [bookings, setBookings] = useState([]);

    useEffect(() => {
        fetchBoats();
    }, []);

    useEffect(() => {
        if (selectedBoat) {
            fetchAvailability();
            fetchBookings();
        }
    }, [selectedBoat, selectedDate]);

    const fetchBoats = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/dashboard', {
                headers: { Authorization: `Bearer ${token}` },
            });
            const boatList = response.data.boats || [];
            setBoats(boatList);
            if (boatList.length > 0 && !selectedBoat) {
                setSelectedBoat(boatList[0].id);
            }
        } catch (error) {
            console.error('Error fetching boats:', error);
        }
    };

    const fetchAvailability = async () => {
        if (!selectedBoat) return;

        try {
            const token = localStorage.getItem('token');
            const startDate = new Date(selectedDate);
            startDate.setMonth(startDate.getMonth() - 1);
            const endDate = new Date(selectedDate);
            endDate.setMonth(endDate.getMonth() + 2);

            const response = await axios.get(`/api/boats/${selectedBoat}/availability`, {
                params: {
                    start_date: format(startDate, 'yyyy-MM-dd'),
                    end_date: format(endDate, 'yyyy-MM-dd'),
                },
                headers: { Authorization: `Bearer ${token}` },
            });
            setAvailability(response.data.calendar || {});
        } catch (error) {
            console.error('Error fetching availability:', error);
        }
    };

    const fetchBookings = async () => {
        if (!selectedBoat) return;

        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/dashboard/bookings', {
                params: { boat_id: selectedBoat },
                headers: { Authorization: `Bearer ${token}` },
            });
            setBookings(response.data.data || []);
        } catch (error) {
            console.error('Error fetching bookings:', error);
        }
    };

    const tileClassName = ({ date }) => {
        const dateStr = format(date, 'yyyy-MM-dd');
        const dayAvailability = availability[dateStr];

        if (!dayAvailability) return '';

        if (!dayAvailability.available) {
            return 'bg-red-100 text-red-800';
        }

        // Check if there are bookings on this date
        const hasBooking = bookings.some(booking => {
            const start = new Date(booking.start_time);
            const end = new Date(booking.end_time);
            return date >= start && date <= end;
        });

        return hasBooking ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
    };

    const handleBlockDate = async (date) => {
        if (!confirm(`Block ${format(date, 'MMM dd, yyyy')}?`)) return;

        try {
            const token = localStorage.getItem('token');
            await axios.post(`/api/boats/${selectedBoat}/block-dates`, {
                dates: [format(date, 'yyyy-MM-dd')],
                reason: 'Unavailable',
            }, {
                headers: { Authorization: `Bearer ${token}` },
            });
            fetchAvailability();
        } catch (error) {
            alert('Failed to block date');
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Availability Calendar</h1>
                <p className="mt-2 text-gray-600">Manage your boat availability</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2">
                    <div className="bg-white rounded-lg shadow p-4 sm:p-6">
                        {boats.length > 0 && (
                            <select
                                value={selectedBoat || ''}
                                onChange={(e) => setSelectedBoat(parseInt(e.target.value))}
                                className="mb-4 w-full border rounded-md px-3 py-2"
                            >
                                {boats.map((boat) => (
                                    <option key={boat.id} value={boat.id}>
                                        {boat.name}
                                    </option>
                                ))}
                            </select>
                        )}
                        <Calendar
                            onChange={setSelectedDate}
                            value={selectedDate}
                            tileClassName={tileClassName}
                            onClickDay={handleBlockDate}
                            className="w-full"
                        />
                        <div className="mt-4 flex flex-wrap gap-4 text-sm">
                            <div className="flex items-center">
                                <div className="w-4 h-4 bg-green-100 border border-green-300 mr-2"></div>
                                <span>Available</span>
                            </div>
                            <div className="flex items-center">
                                <div className="w-4 h-4 bg-yellow-100 border border-yellow-300 mr-2"></div>
                                <span>Booked</span>
                            </div>
                            <div className="flex items-center">
                                <div className="w-4 h-4 bg-red-100 border border-red-300 mr-2"></div>
                                <span>Blocked</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div className="bg-white rounded-lg shadow p-4 sm:p-6">
                        <h3 className="font-semibold mb-4">Bookings for {format(selectedDate, 'MMM dd, yyyy')}</h3>
                        <div className="space-y-3">
                            {bookings
                                .filter(booking => {
                                    const bookingDate = new Date(booking.start_time);
                                    return format(bookingDate, 'yyyy-MM-dd') === format(selectedDate, 'yyyy-MM-dd');
                                })
                                .map((booking) => (
                                    <div key={booking.id} className="border rounded p-3">
                                        <p className="font-medium text-sm">{booking.customer?.name}</p>
                                        <p className="text-xs text-gray-600">
                                            {format(new Date(booking.start_time), 'hh:mm a')} - {format(new Date(booking.end_time), 'hh:mm a')}
                                        </p>
                                        <span className={`inline-block mt-2 px-2 py-1 rounded text-xs ${
                                            booking.status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                            booking.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-gray-100 text-gray-800'
                                        }`}>
                                            {booking.status}
                                        </span>
                                    </div>
                                ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

