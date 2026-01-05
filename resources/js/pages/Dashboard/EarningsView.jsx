import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { format, subMonths } from 'date-fns';

export default function EarningsView() {
    const [earnings, setEarnings] = useState(null);
    const [loading, setLoading] = useState(true);
    const [startDate, setStartDate] = useState(format(subMonths(new Date(), 1), 'yyyy-MM-dd'));
    const [endDate, setEndDate] = useState(format(new Date(), 'yyyy-MM-dd'));

    useEffect(() => {
        fetchEarnings();
    }, [startDate, endDate]);

    const fetchEarnings = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/dashboard/earnings', {
                params: {
                    start_date: startDate,
                    end_date: endDate,
                },
                headers: { Authorization: `Bearer ${token}` },
            });
            setEarnings(response.data);
        } catch (error) {
            console.error('Error fetching earnings:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading earnings...</div>;
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Earnings Overview</h1>
                <p className="mt-2 text-gray-600">Track your earnings and payments</p>
            </div>

            <div className="bg-white rounded-lg shadow p-4 sm:p-6">
                <div className="flex flex-col sm:flex-row gap-4 mb-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="border rounded-md px-3 py-2 w-full sm:w-auto"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            className="border rounded-md px-3 py-2 w-full sm:w-auto"
                        />
                    </div>
                </div>

                <div className="bg-blue-50 rounded-lg p-6 mb-6">
                    <p className="text-sm text-gray-600 mb-1">Total Earnings</p>
                    <p className="text-3xl font-bold text-blue-600">${earnings?.total?.toFixed(2) || '0.00'}</p>
                    <p className="text-xs text-gray-500 mt-2">
                        {format(new Date(startDate), 'MMM dd, yyyy')} - {format(new Date(endDate), 'MMM dd, yyyy')}
                    </p>
                </div>

                <div className="space-y-3">
                    <h3 className="font-semibold mb-4">Earnings History</h3>
                    {earnings?.earnings?.map((earning) => (
                        <div key={earning.id} className="border rounded-lg p-4 flex justify-between items-center">
                            <div>
                                <p className="font-medium">
                                    {format(new Date(earning.processed_at), 'MMM dd, yyyy')}
                                </p>
                                <p className="text-sm text-gray-600">
                                    {earning.paymentable_type === 'App\\Models\\Booking' ? 'Booking' : 'Order'}
                                </p>
                            </div>
                            <p className="text-lg font-semibold text-green-600">
                                ${(earning.captain_amount || earning.vendor_amount)?.toFixed(2)}
                            </p>
                        </div>
                    ))}
                </div>

                {(!earnings?.earnings || earnings.earnings.length === 0) && (
                    <div className="text-center py-12 text-gray-500">
                        No earnings found for this period
                    </div>
                )}
            </div>
        </div>
    );
}

