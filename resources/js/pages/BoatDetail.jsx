import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import axios from 'axios';

export default function BoatDetail() {
    const { id } = useParams();
    const [boat, setBoat] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchBoat();
    }, [id]);

    const fetchBoat = async () => {
        try {
            const response = await axios.get(`/api/boats/${id}`);
            setBoat(response.data);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching boat:', error);
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading...</div>;
    }

    if (!boat) {
        return <div className="text-center py-12">Boat not found</div>;
    }

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div className="bg-white rounded-lg shadow-lg overflow-hidden">
                <div className="h-96 bg-gray-200"></div>
                <div className="p-8">
                    <h1 className="text-3xl font-bold text-gray-900">{boat.name}</h1>
                    <p className="text-gray-600 mt-2">{boat.location}</p>
                    <p className="text-gray-700 mt-4">{boat.description}</p>
                    <div className="mt-6">
                        <p className="text-2xl font-bold text-blue-600">${boat.hourly_rate}/hour</p>
                        <p className="text-gray-600">or ${boat.daily_rate}/day</p>
                    </div>
                </div>
            </div>
        </div>
    );
}

