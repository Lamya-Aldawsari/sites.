import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function OnDemandBooking() {
    const [location, setLocation] = useState({ lat: null, lng: null });
    const [nearbyBoats, setNearbyBoats] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedBoat, setSelectedBoat] = useState(null);
    const [duration, setDuration] = useState(60);
    const navigate = useNavigate();

    useEffect(() => {
        // Get user's current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setLocation({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    });
                    findNearbyBoats(position.coords.latitude, position.coords.longitude);
                },
                (error) => {
                    console.error('Error getting location:', error);
                    // Default to Miami Beach
                    setLocation({ lat: 25.7907, lng: -80.1300 });
                }
            );
        }
    }, []);

    const findNearbyBoats = async (lat, lng) => {
        setLoading(true);
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/on-demand/nearby-boats', {
                params: { latitude: lat, longitude: lng, radius_km: 10 },
                headers: { Authorization: `Bearer ${token}` },
            });
            setNearbyBoats(response.data.boats || []);
        } catch (error) {
            console.error('Error finding boats:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleBookNow = async (boat) => {
        if (!location.lat || !location.lng) {
            alert('Please enable location services');
            return;
        }

        try {
            const token = localStorage.getItem('token');
            const response = await axios.post(
                '/api/on-demand/bookings',
                {
                    boat_id: boat.id,
                    pickup_latitude: location.lat,
                    pickup_longitude: location.lng,
                    duration_minutes: duration,
                },
                {
                    headers: { Authorization: `Bearer ${token}` },
                }
            );

            alert(`Booking created! Estimated arrival: ${response.data.estimated_arrival_minutes} minutes`);
            navigate('/dashboard');
        } catch (error) {
            alert(error.response?.data?.message || 'Failed to create booking');
        }
    };

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 sm:mb-6 md:mb-8">On-Demand Boat Booking</h1>
            
            <div className="mb-4 sm:mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Trip Duration (minutes)
                </label>
                <input
                    type="number"
                    min="30"
                    max="480"
                    value={duration}
                    onChange={(e) => setDuration(parseInt(e.target.value))}
                    className="border rounded-md px-3 py-2 w-full sm:w-32 text-sm sm:text-base"
                />
            </div>

            {loading ? (
                <div className="text-center py-12">Finding nearby boats...</div>
            ) : nearbyBoats.length === 0 ? (
                <div className="text-center py-12 text-gray-500">
                    No boats available nearby. Try again later.
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    {nearbyBoats.map((boat) => (
                        <div key={boat.id} className="bg-white rounded-lg shadow-md overflow-hidden">
                            <div className="h-40 sm:h-48 bg-gray-200"></div>
                            <div className="p-4 sm:p-6">
                                <h3 className="text-lg sm:text-xl font-semibold text-gray-900">{boat.name}</h3>
                                <p className="text-sm sm:text-base text-gray-600 mt-2">{boat.type}</p>
                                <p className="text-xs sm:text-sm text-green-600 mt-2">
                                    ETA: ~{boat.estimated_arrival_minutes} minutes
                                </p>
                                <p className="text-blue-600 font-bold mt-3 sm:mt-4 text-base sm:text-lg">
                                    ${boat.hourly_rate}/hour
                                </p>
                                <button
                                    onClick={() => handleBookNow(boat)}
                                    className="mt-3 sm:mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-sm sm:text-base"
                                >
                                    Book Now
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

