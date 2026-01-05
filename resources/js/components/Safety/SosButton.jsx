import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function SosButton({ bookingId, onSosSent }) {
    const [location, setLocation] = useState(null);
    const [loading, setLoading] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [error, setError] = useState(null);
    const [offlineMode, setOfflineMode] = useState(false);

    useEffect(() => {
        // Try to get current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setLocation({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                    });
                },
                (err) => {
                    console.error('Location error:', err);
                    setError('Unable to get location. SOS will use last known location.');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0,
                }
            );
        } else {
            setError('Geolocation not supported');
        }

        // Check online status
        const handleOnline = () => setOfflineMode(false);
        const handleOffline = () => setOfflineMode(true);
        
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
        setOfflineMode(!navigator.onLine);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const handleSos = async () => {
        if (!showConfirm) {
            setShowConfirm(true);
            setTimeout(() => setShowConfirm(false), 5000); // Auto-cancel after 5 seconds
            return;
        }

        setLoading(true);
        setError(null);

        try {
            // Store SOS data locally first (offline support)
            const sosData = {
                booking_id: bookingId,
                latitude: location?.lat || 0,
                longitude: location?.lng || 0,
                message: 'Emergency SOS alert activated',
                timestamp: new Date().toISOString(),
            };

            // Store in localStorage for offline retry
            const pendingSos = JSON.parse(localStorage.getItem('pending_sos') || '[]');
            pendingSos.push(sosData);
            localStorage.setItem('pending_sos', JSON.stringify(pendingSos));

            if (!offlineMode && navigator.onLine) {
                const token = localStorage.getItem('token');
                await axios.post(
                    '/api/sos',
                    {
                        booking_id: bookingId,
                        latitude: location?.lat || 0,
                        longitude: location?.lng || 0,
                        message: 'Emergency SOS alert activated',
                    },
                    {
                        headers: { Authorization: `Bearer ${token}` },
                        timeout: 10000, // 10 second timeout
                    }
                );

                // Remove from pending if successful
                const updated = pendingSos.filter(sos => sos.timestamp !== sosData.timestamp);
                localStorage.setItem('pending_sos', JSON.stringify(updated));

                alert('SOS alert sent! Emergency services have been notified.');
                if (onSosSent) onSosSent();
            } else {
                // Offline mode - will retry when online
                alert('SOS alert saved. Will be sent when connection is restored.');
            }
        } catch (err) {
            console.error('SOS error:', err);
            setError('Failed to send SOS. Alert saved for retry.');
            // Keep in localStorage for retry
        } finally {
            setLoading(false);
            setShowConfirm(false);
        }
    };

    // Retry pending SOS alerts when online
    useEffect(() => {
        if (!offlineMode && navigator.onLine) {
            const pendingSos = JSON.parse(localStorage.getItem('pending_sos') || '[]');
            if (pendingSos.length > 0) {
                pendingSos.forEach(async (sos) => {
                    try {
                        const token = localStorage.getItem('token');
                        await axios.post('/api/sos', sos, {
                            headers: { Authorization: `Bearer ${token}` },
                        });
                    } catch (err) {
                        console.error('Failed to retry SOS:', err);
                    }
                });
                localStorage.removeItem('pending_sos');
            }
        }
    }, [offlineMode]);

    return (
        <div className="fixed bottom-4 right-4 z-50">
            {error && (
                <div className="mb-2 bg-yellow-100 border border-yellow-400 text-yellow-800 px-3 py-2 rounded text-xs max-w-xs">
                    {error}
                </div>
            )}
            {offlineMode && (
                <div className="mb-2 bg-orange-100 border border-orange-400 text-orange-800 px-3 py-2 rounded text-xs">
                    Offline Mode - SOS will be sent when online
                </div>
            )}
            <button
                onClick={handleSos}
                disabled={loading}
                className={`bg-red-600 hover:bg-red-700 text-white font-bold py-4 px-6 rounded-full shadow-lg transform transition-transform hover:scale-105 ${
                    loading ? 'opacity-50 cursor-not-allowed' : ''
                } ${showConfirm ? 'animate-pulse ring-4 ring-red-300' : ''}`}
                style={{ minWidth: '80px' }}
            >
                {loading ? (
                    <span className="text-sm">Sending...</span>
                ) : showConfirm ? (
                    <span className="text-sm font-bold">CONFIRM SOS</span>
                ) : (
                    <span className="text-sm font-bold">SOS</span>
                )}
            </button>
        </div>
    );
}

