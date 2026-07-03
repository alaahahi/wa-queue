import axios from 'axios';
import { tenantBasePath } from '@/utils/tenant';

const api = axios.create({
    baseURL: `${tenantBasePath()}/api/v1`,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

export default api;
