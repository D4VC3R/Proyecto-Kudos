export const normalizePaginatedData = (responseData) => {
    const rawData = responseData?.data;

    if (Array.isArray(rawData)) {
        return rawData;
    }

    if (rawData && Array.isArray(rawData.data)) {
        return rawData.data;
    }

    return [];
};