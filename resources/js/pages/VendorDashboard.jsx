import React, { useState, useEffect } from 'react';
import axios from 'axios';

export default function VendorDashboard() {
    const [dashboard, setDashboard] = useState(null);
    const [inventory, setInventory] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchDashboard();
        fetchInventory();
    }, []);

    const fetchDashboard = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/vendor/dashboard', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setDashboard(response.data);
        } catch (error) {
            console.error('Error fetching dashboard:', error);
        }
    };

    const fetchInventory = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/vendor/inventory', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setInventory(response.data.data || response.data);
        } catch (error) {
            console.error('Error fetching inventory:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading...</div>;
    }

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 className="text-3xl font-bold text-gray-900 mb-8">Vendor Dashboard</h1>
            
            {dashboard && (
                <div className="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                    <div className="bg-white rounded-lg shadow p-6">
                        <p className="text-sm text-gray-600">Equipment Items</p>
                        <p className="text-2xl font-bold">{dashboard.stats.equipment_count}</p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <p className="text-sm text-gray-600">Available Quantity</p>
                        <p className="text-2xl font-bold">{dashboard.stats.available_quantity}</p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <p className="text-sm text-gray-600">Total Orders</p>
                        <p className="text-2xl font-bold">{dashboard.stats.total_orders}</p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <p className="text-sm text-gray-600">Pending Orders</p>
                        <p className="text-2xl font-bold">{dashboard.stats.pending_orders}</p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <p className="text-sm text-gray-600">Total Revenue</p>
                        <p className="text-2xl font-bold">${dashboard.stats.total_revenue}</p>
                    </div>
                </div>
            )}

            <div className="bg-white rounded-lg shadow-md p-6">
                <h2 className="text-xl font-semibold mb-4">Inventory</h2>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {inventory.map((item) => (
                                <tr key={item.id}>
                                    <td className="px-6 py-4 whitespace-nowrap">{item.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{item.category}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">${item.daily_rate}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">{item.quantity_available}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded ${item.is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                            {item.is_available ? 'Available' : 'Unavailable'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

