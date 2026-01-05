import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

export default function VerificationStatus() {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        fetchStatus();
    }, []);

    const fetchStatus = async () => {
        try {
            const token = localStorage.getItem('token');
            const response = await axios.get('/api/dashboard', {
                headers: { Authorization: `Bearer ${token}` },
            });
            setStatus(response.data.verification_status);
        } catch (error) {
            console.error('Error fetching verification status:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-12">Loading...</div>;
    }

    if (!status) {
        return <div className="text-center py-12">No verification data available</div>;
    }

    const progress = (status.approved_count / status.required_documents.length) * 100;

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Verification Status</h1>
                <p className="mt-2 text-gray-600">Track your verification progress</p>
            </div>

            <div className="bg-white rounded-lg shadow p-4 sm:p-6">
                <div className="mb-6">
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm font-medium text-gray-700">Overall Progress</span>
                        <span className="text-sm font-medium text-gray-700">
                            {status.approved_count} / {status.required_documents.length} approved
                        </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-4">
                        <div
                            className={`h-4 rounded-full transition-all ${
                                status.is_verified ? 'bg-green-500' : 'bg-blue-500'
                            }`}
                            style={{ width: `${progress}%` }}
                        ></div>
                    </div>
                    {status.is_verified && (
                        <p className="mt-2 text-green-600 font-semibold">✓ Verified and Active</p>
                    )}
                </div>

                <div className="space-y-4">
                    {status.required_documents.map((docType) => {
                        const doc = status.uploaded_documents[docType];
                        return (
                            <div
                                key={docType}
                                className="border rounded-lg p-4 flex items-center justify-between"
                            >
                                <div className="flex-1">
                                    <h3 className="font-medium capitalize">
                                        {docType.replace('_', ' ')}
                                    </h3>
                                    {doc.status === 'approved' && doc.reviewed_at && (
                                        <p className="text-sm text-gray-600">
                                            Approved on {new Date(doc.reviewed_at).toLocaleDateString()}
                                        </p>
                                    )}
                                    {doc.status === 'rejected' && (
                                        <p className="text-sm text-red-600">
                                            Rejected - Please upload again
                                        </p>
                                    )}
                                </div>
                                <div className="flex items-center space-x-4">
                                    <span
                                        className={`px-3 py-1 rounded-full text-sm font-medium ${
                                            doc.status === 'approved'
                                                ? 'bg-green-100 text-green-800'
                                                : doc.status === 'rejected'
                                                ? 'bg-red-100 text-red-800'
                                                : doc.status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-gray-100 text-gray-800'
                                        }`}
                                    >
                                        {doc.status === 'not_uploaded'
                                            ? 'Not Uploaded'
                                            : doc.status.charAt(0).toUpperCase() + doc.status.slice(1)}
                                    </span>
                                    {doc.status === 'not_uploaded' && (
                                        <button
                                            onClick={() => navigate('/verification')}
                                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm"
                                        >
                                            Upload
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

