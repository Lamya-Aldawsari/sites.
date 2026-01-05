import React, { useState } from 'react';
import axios from 'axios';

export default function SosButton({ bookingId, currentLocation }) {
    const [loading, setLoading] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const handleSos = async () => {
        if (!showConfirm) {
            setShowConfirm(true);
            return;
        }

        setLoading(true);
        try {
            const token = localStorage.getItem('token');
            await axios.post(
                '/api/sos',
                {
                    booking_id: bookingId,
                    latitude: currentLocation?.lat || 0,
                    longitude: currentLocation?.lng || 0,
                    message: 'Emergency SOS alert activated',
                },
                {
                    headers: { Authorization: `Bearer ${token}` },
                }
            );
            alert('SOS alert sent! Emergency services have been notified.');
        } catch (error) {
            alert('Failed to send SOS alert. Please try again.');
        } finally {
            setLoading(false);
            setShowConfirm(false);
        }
    };

    return (
        <div className="fixed bottom-4 right-4 z-50">
            <button
                onClick={handleSos}
                disabled={loading}
                className={`bg-red-600 hover:bg-red-700 text-white font-bold py-4 px-6 rounded-full shadow-lg transform transition-transform hover:scale-105 ${
                    loading ? 'opacity-50 cursor-not-allowed' : ''
                }`}
            >
                {showConfirm ? (
                    <span className="text-sm sm:text-base">Confirm SOS</span>
                ) : (
                    <span className="text-sm sm:text-base">SOS</span>
                )}
            </button>
        </div>
    );
}

