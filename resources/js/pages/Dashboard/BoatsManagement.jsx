import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function BoatsManagement() {
    const [boats, setBoats] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editingBoat, setEditingBoat] = useState(null);
    const navigate = useNavigate();

    useEffect(() => {
        fetchBoats();
    }, []);

    const fetchBoats = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/dashboard', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setBoats(response.data.boats || []);
        } catch (error) {
            console.error('Error fetching boats:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (boatId) => {
        if (!confirm('Are you sure you want to delete this boat?')) return;

        try {
            const token = localStorage.getItem('token');
            await axios.delete(`/api/boats/${boatId}`, {
                headers: { Authorization: `Bearer ${token}` },
            });
            fetchBoats();
        } catch (error) {
            alert('Failed to delete boat');
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading boats...</div>;
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Boat Management</h1>
                    <p className="mt-2 text-gray-600">Manage your boat listings</p>
                </div>
                <button
                    onClick={() => navigate('/dashboard/boats/new')}
                    className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm sm:text-base"
                >
                    + Add Boat
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                {boats.map((boat) => (
                    <div key={boat.id} className="bg-white rounded-lg shadow overflow-hidden">
                        <div className="h-48 bg-gray-200">
                            {boat.images && boat.images[0] && (
                                <img src={boat.images[0]} alt={boat.name} className="w-full h-full object-cover" />
                            )}
                        </div>
                        <div className="p-4 sm:p-6">
                            <h3 className="text-lg font-semibold">{boat.name}</h3>
                            <p className="text-sm text-gray-600 mt-1 capitalize">{boat.type}</p>
                            <div className="mt-4 flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Status</p>
                                    <span className={`inline-block px-2 py-1 rounded text-xs mt-1 ${
                                        boat.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {boat.is_available ? 'Available' : 'Unavailable'}
                                    </span>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm text-gray-600">Rate</p>
                                    <p className="font-semibold">${boat.hourly_rate}/hr</p>
                                </div>
                            </div>
                            <div className="mt-4 flex space-x-2">
                                <button
                                    onClick={() => navigate(`/dashboard/boats/${boat.id}/edit`)}
                                    className="flex-1 bg-blue-50 text-blue-700 py-2 px-3 rounded text-sm hover:bg-blue-100"
                                >
                                    Edit
                                </button>
                                <button
                                    onClick={() => handleDelete(boat.id)}
                                    className="flex-1 bg-red-50 text-red-700 py-2 px-3 rounded text-sm hover:bg-red-100"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {boats.length === 0 && (
                <div className="text-center py-12 bg-white rounded-lg shadow">
                    <p className="text-gray-500 mb-4">No boats listed yet</p>
                    <button
                        onClick={() => navigate('/dashboard/boats/new')}
                        className="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700"
                    >
                        Add Your First Boat
                    </button>
                </div>
            )}
        </div>
    );
}

