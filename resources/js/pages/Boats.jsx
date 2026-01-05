import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';

export default function Boats() {
    const [boats, setBoats] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchBoats();
    }, []);

    const fetchBoats = async () => {
        try {
            const response = await axios.get('/api/boats');
            setBoats(response.data.data || response.data);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching boats:', error);
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading...</div>;
    }

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 sm:mb-6 md:mb-8">Available Boats</h1>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                {boats.map((boat) => (
                    <Link key={boat.id} to={`/boats/${boat.id}`} className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div className="h-40 sm:h-48 bg-gray-200"></div>
                        <div className="p-4 sm:p-6">
                            <h3 className="text-lg sm:text-xl font-semibold text-gray-900">{boat.name}</h3>
                            <p className="text-sm sm:text-base text-gray-600 mt-2">{boat.type}</p>
                            <p className="text-sm sm:text-base text-gray-600">Capacity: {boat.capacity} guests</p>
                            <p className="text-blue-600 font-bold mt-3 sm:mt-4 text-base sm:text-lg">${boat.hourly_rate}/hour</p>
                        </div>
                    </Link>
                ))}
            </div>
        </div>
    );
}

