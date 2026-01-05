import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import axios from 'axios';
import LiveTrackingMap from '../../components/Safety/LiveTrackingMap';
import SosButton from '../../components/Safety/SosButton';
import { usePusher } from '../../hooks/usePusher';

export default function TripTracking() {
    const { bookingId } = useParams();
    const [tripLog, setTripLog] = useState(null);
    const [booking, setBooking] = useState(null);
    const [loading, setLoading] = useState(true);
    const { echo } = usePusher();

    useEffect(() => {
        fetchTripData();
    }, [bookingId]);

    useEffect(() => {
        if (echo && tripLog) {
            // Listen for location updates
            const channel = echo.private(`trip.${tripLog.id}`);
            
            channel.listen('.location.updated', (data) => {
                console.log('Location updated:', data);
                // Update trip log with new location
            });

            return () => {
                channel.stopListening('.location.updated');
            };
        }
    }, [echo, tripLog]);

    const fetchTripData = async () => {
        try {
            const token = localStorage.getItem('token');
            const [bookingRes, tripsRes] = await Promise.all([
                axios.get(`/api/bookings/${bookingId}`, {
                    headers: { Authorization: `Bearer ${token}` },
                }),
                axios.get('/api/trips/active', {
                    headers: { Authorization: `Bearer ${token}` },
                }),
            ]);

            setBooking(bookingRes.data);
            const activeTrip = tripsRes.data.find(t => t.trip_log.booking_id === parseInt(bookingId));
            if (activeTrip) {
                setTripLog(activeTrip.trip_log);
            }
        } catch (error) {
            console.error('Error fetching trip data:', error);
        } finally {
            setLoading(false);
        }
    };

    const startTrip = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.post(`/api/bookings/${bookingId}/start-trip`, {}, {
                headers: { Authorization: `Bearer ${token}` },
            });
            setTripLog(response.data);
        } catch (error) {
            alert('Failed to start trip');
        }
    };

    const endTrip = async () => {
        if (!confirm('End this trip?')) return;

        try {
            const token = localStorage.getItem('token');
            await axios.post(`/api/trips/${tripLog.id}/end-trip`, {}, {
                headers: { Authorization: `Bearer ${token}` },
            });
            fetchTripData();
        } catch (error) {
            alert('Failed to end trip');
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading trip data...</div>;
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Live Trip Tracking</h1>
                <p className="mt-2 text-gray-600">Real-time GPS tracking for active trips</p>
            </div>

            {!tripLog ? (
                <div className="bg-white rounded-lg shadow p-6 text-center">
                    <p className="text-gray-600 mb-4">Trip not started yet</p>
                    <button
                        onClick={startTrip}
                        className="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"
                    >
                        Start Trip
                    </button>
                </div>
            ) : (
                <>
                    {tripLog.status === 'active' && (
                        <>
                            <LiveTrackingMap tripLogId={tripLog.id} bookingId={bookingId} />
                            <SosButton bookingId={bookingId} />
                        </>
                    )}
                    <div className="bg-white rounded-lg shadow p-4 sm:p-6">
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="font-semibold">Trip Information</h3>
                            {tripLog.status === 'active' && (
                                <button
                                    onClick={endTrip}
                                    className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm"
                                >
                                    End Trip
                                </button>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p className="text-gray-600">Started</p>
                                <p className="font-medium">
                                    {new Date(tripLog.trip_started_at).toLocaleString()}
                                </p>
                            </div>
                            {tripLog.trip_ended_at && (
                                <div>
                                    <p className="text-gray-600">Ended</p>
                                    <p className="font-medium">
                                        {new Date(tripLog.trip_ended_at).toLocaleString()}
                                    </p>
                                </div>
                            )}
                            <div>
                                <p className="text-gray-600">Distance</p>
                                <p className="font-medium">{tripLog.total_distance_nm} nm</p>
                            </div>
                            <div>
                                <p className="text-gray-600">Max Speed</p>
                                <p className="font-medium">{tripLog.max_speed_knots || 0} knots</p>
                            </div>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

