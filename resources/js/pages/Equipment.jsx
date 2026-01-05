import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function Equipment() {
    const [equipment, setEquipment] = useState([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        fetchEquipment();
    }, []);

    const fetchEquipment = async () => {
        try {
            const response = await axios.get('/api/equipment');
            setEquipment(response.data.data || response.data);
            setLoading(false);
        } catch (error) {
            console.error('Error fetching equipment:', error);
            setLoading(false);
        }
    };

    const addToCart = async (equipmentId) => {
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                navigate('/login');
                return;
            }
            await axios.post(
                '/api/cart/items',
                { equipment_id: equipmentId, quantity: 1 },
                {
                    headers: { Authorization: `Bearer ${token}` },
                }
            );
            alert('Added to cart!');
        } catch (error) {
            alert(error.response?.data?.message || 'Failed to add to cart');
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading...</div>;
    }

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 sm:mb-6 md:mb-8">Marine Equipment</h1>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                {equipment.map((item) => (
                    <div key={item.id} className="bg-white rounded-lg shadow-md overflow-hidden">
                        <div className="h-40 sm:h-48 bg-gray-200"></div>
                        <div className="p-4 sm:p-6">
                            <h3 className="text-lg sm:text-xl font-semibold text-gray-900">{item.name}</h3>
                            <p className="text-sm sm:text-base text-gray-600 mt-2">{item.category}</p>
                            <p className="text-blue-600 font-bold mt-3 sm:mt-4 text-base sm:text-lg">${item.daily_rate}</p>
                            <p className="text-xs sm:text-sm text-gray-500 mt-1">Available: {item.quantity_available}</p>
                            <button
                                onClick={() => addToCart(item.id)}
                                className="mt-3 sm:mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-sm sm:text-base"
                            >
                                Add to Cart
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

