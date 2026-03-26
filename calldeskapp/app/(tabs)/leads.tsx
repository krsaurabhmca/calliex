import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator, RefreshControl, TouchableOpacity, Linking, TextInput, Platform, ScrollView, Modal, Alert } from 'react-native';
import { makeCall } from '../../services/dialer';
import { useFocusEffect } from '@react-navigation/native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { apiCall } from '../../services/api';
import { Phone, User, Plus, Search, MessageSquare, MapPin, X, LayoutGrid, CheckCircle2, UserPlus, Trash2, Edit3, MessageCircle, ChevronRight, Activity } from 'lucide-react-native';
import { useSnackbar } from '../../context/SnackbarContext';
import { useRouter } from 'expo-router';

export default function LeadsScreen() {
    const { showSnackbar } = useSnackbar();
    const insets = useSafeAreaInsets();
    const router = useRouter();

    const [leads, setLeads] = useState([]);
    const [statuses, setStatuses] = useState<any[]>([]);
    const [user, setUser] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('All');

    // Action Menu State
    const [menuVisible, setMenuVisible] = useState(false);
    const [selectedLead, setSelectedLead] = useState<any>(null);
    const [executives, setExecutives] = useState<any[]>([]);
    const [assignModalVisible, setAssignModalVisible] = useState(false);

    const fetchData = async () => {
        const [leadsRes, metaRes] = await Promise.all([
            apiCall('leads.php'),
            apiCall('leads.php?action=form_metadata')
        ]);

        if (leadsRes.success) setLeads(leadsRes.data || []);
        if (metaRes.success && metaRes.data) {
            setStatuses(metaRes.data.statuses || []);
            setUser(metaRes.data.user || null);
        }
        setLoading(false);
    };

    useFocusEffect(
        useCallback(() => {
            fetchData();
        }, [])
    );

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchData();
        setRefreshing(false);
    };

    const handleCall = (mobile: string) => {
        if (!mobile) {
            showSnackbar('Number Error');
            return;
        }
        makeCall(mobile);
    };

    const handleWhatsApp = (mobile: string) => {
        if (!mobile) {
            showSnackbar('Number Error');
            return;
        }
        const cleanMobile = mobile.replace(/\D/g, '');
        Linking.openURL(`whatsapp://send?phone=91${cleanMobile}&text=${encodeURIComponent('Hello from Calldesk CRM.')}`);
    };

    const getStatusTheme = (statusName: string) => {
        const s = statuses.find(x => x && x.status_name === statusName);
        const color = s?.color_code || '#6366f1';
        return {
            bg: color + '10',
            border: color + '25',
            text: color
        };
    };

    // Card Actions
    const handleLongPress = (lead: any) => {
        if (!lead) return;
        setSelectedLead(lead);
        setMenuVisible(true);
    };

    const handleDelete = () => {
        if (!selectedLead) return;
        Alert.alert(
            "Delete Lead",
            `Are you sure you want to permanently delete ${selectedLead.name}?`,
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Delete",
                    style: "destructive",
                    onPress: async () => {
                        const res = await apiCall(`leads.php?id=${selectedLead.id}`, 'DELETE');
                        if (res.success) {
                            showSnackbar('Lead Deleted');
                            fetchData();
                            setMenuVisible(false);
                        } else {
                            showSnackbar(res.message || 'Delete failed', 'error');
                        }
                    }
                }
            ]
        );
    };

    const startAssignment = async () => {
        const res = await apiCall('leads.php?action=executives', 'GET');
        if (res.success) {
            setExecutives(res.data || []);
            setAssignModalVisible(true);
        } else {
            showSnackbar('Could not fetch executives', 'error');
        }
    };

    const completeAssignment = async (execId: number) => {
        if (!selectedLead) return;
        const res = await apiCall('leads.php', 'POST', {
            action: 'bulk_assign',
            lead_ids: selectedLead.id.toString(),
            assigned_to: execId
        });
        if (res.success) {
            showSnackbar('Lead Assigned');
            setAssignModalVisible(false);
            setMenuVisible(false);
            fetchData();
        } else {
            showSnackbar(res.message || 'Assignment failed', 'error');
        }
    };

    const filteredLeads = Array.isArray(leads) ? leads.filter((lead: any) => {
        if (!lead || !lead.name) return false;
        const q = searchQuery.toLowerCase();
        const matchesSearch = lead.name.toLowerCase().includes(q) || (lead.mobile && lead.mobile.includes(q));
        const matchesStatus = statusFilter === 'All' || lead.status === statusFilter;
        return matchesSearch && matchesStatus;
    }) : [];

    const renderLead = ({ item }: any) => {
        if (!item) return null;
        const theme = getStatusTheme(item.status);
        const displayMobile = item.display_mobile || item.mobile;

        return (
            <TouchableOpacity
                style={styles.card}
                activeOpacity={0.8}
                onPress={() => router.push({ pathname: '/lead-action', params: { leadId: item.id, leadName: item.name, autoAction: 'update', autoNumber: item.mobile, focusInteraction: 'true' } })}
                onLongPress={() => handleLongPress(item)}
            >
                <View style={[styles.sideIndicator, { backgroundColor: theme.text }]} />

                <View style={styles.cardContent}>
                    <View style={styles.avatar}>
                        <Text style={styles.avatarText}>{item.name ? item.name.charAt(0).toUpperCase() : '?'}</Text>
                    </View>

                    <View style={styles.cardInfo}>
                        <View style={styles.nameRow}>
                            <Text style={styles.leadName} numberOfLines={1}>{item.name}</Text>
                            <View style={[styles.statusBadge, { backgroundColor: theme.bg, borderColor: theme.border }]}>
                                <Text style={[styles.statusText, { color: theme.text }]}>{item.status}</Text>
                            </View>
                        </View>

                        <View style={styles.detailsGrid}>
                            <View style={styles.contactRow}>
                                <Phone size={11} color="#6366f1" />
                                <Text style={styles.mobileText}>{displayMobile}</Text>
                            </View>
                            {(item.district_name || item.state_name) && (
                                <View style={styles.locationRow}>
                                    <MapPin size={10} color="#94a3b8" />
                                    <Text style={styles.locationText} numberOfLines={1}>{item.district_name || item.state_name}</Text>
                                </View>
                            )}
                        </View>
                    </View>

                    <View style={styles.actions}>
                        <TouchableOpacity style={[styles.actionBtn, { backgroundColor: '#f0fdf4' }]} onPress={() => handleWhatsApp(item.mobile)}>
                            <MessageSquare size={16} color="#16a34a" />
                        </TouchableOpacity>
                        <TouchableOpacity style={[styles.actionBtn, { backgroundColor: '#eef2ff' }]} onPress={() => handleCall(item.mobile)}>
                            <Phone size={16} color="#4f46e5" fill="#4f46e5" />
                        </TouchableOpacity>
                    </View>
                </View>
            </TouchableOpacity>
        );
    };

    if (loading) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="small" color="#6366f1" />
                <Text style={{ marginTop: 10, fontSize: 10, fontWeight: '800', color: '#94a3b8', letterSpacing: 1 }}>SYNCHRONIZING...</Text>
            </View>
        );
    }

    return (
        <View style={[styles.container]}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.headerDate}>{new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'short' }).toUpperCase()} • {leads.length} LEADS</Text>
                    <Text style={styles.title}>Leads</Text>
                </View>
                <TouchableOpacity style={styles.compactAdd} activeOpacity={0.8} onPress={() => router.push({ pathname: '/lead-action', params: { autoAction: 'add' } })}>
                    <Plus size={20} color="#fff" strokeWidth={3} />
                    <Text style={styles.addText}>NEW</Text>
                </TouchableOpacity>
            </View>

            <View style={styles.searchContainer}>
                <View style={styles.searchBar}>
                    <Search size={16} color="#94a3b8" strokeWidth={2.5} />
                    <TextInput
                        style={styles.searchInput}
                        placeholder="Quick search..."
                        value={searchQuery}
                        onChangeText={setSearchQuery}
                        placeholderTextColor="#94a3b8"
                    />
                    {searchQuery !== '' && (
                        <TouchableOpacity onPress={() => setSearchQuery('')} style={styles.clearBtn}>
                            <X size={14} color="#94a3b8" fill="#f1f5f9" />
                        </TouchableOpacity>
                    )}
                </View>
            </View>

            <View style={styles.filterOuter}>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterStrip}>
                    <TouchableOpacity style={[styles.filterPill, statusFilter === 'All' && styles.filterPillActive]} onPress={() => setStatusFilter('All')}>
                        <LayoutGrid size={12} color={statusFilter === 'All' ? '#fff' : '#64748b'} />
                        <Text style={[styles.filterText, statusFilter === 'All' && styles.filterTextActive]}>All</Text>
                    </TouchableOpacity>
                    {statuses.map(s => (
                        s && (
                            <TouchableOpacity
                                key={s.id}
                                style={[styles.filterPill, statusFilter === s.status_name && { backgroundColor: s.color_code, borderColor: s.color_code }]}
                                onPress={() => setStatusFilter(s.status_name)}
                            >
                                <Text style={[styles.filterText, statusFilter === s.status_name && { color: '#fff' }]}>{s.status_name}</Text>
                            </TouchableOpacity>
                        )
                    ))}
                </ScrollView>
            </View>

            <FlatList
                data={filteredLeads}
                renderItem={renderLead}
                keyExtractor={(item: any) => item?.id?.toString() || Math.random().toString()}
                contentContainerStyle={styles.list}
                showsVerticalScrollIndicator={false}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#6366f1" />}
            />

            {/* Bottom Menu Modal */}
            <Modal visible={menuVisible} transparent animationType="slide" onRequestClose={() => setMenuVisible(false)}>
                <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setMenuVisible(false)}>
                    <View style={styles.modalContent}>
                        <View style={styles.modalHandle} />
                        <View style={styles.modalHeader}>
                            <View style={styles.modalAvatar}>
                                <Text style={styles.modalAvatarText}>{selectedLead?.name ? selectedLead.name.charAt(0).toUpperCase() : '?'}</Text>
                            </View>
                            <View>
                                <Text style={styles.modalLeadName}>{selectedLead?.name || 'Unknown Lead'}</Text>
                                <Text style={styles.modalLeadSub}>{selectedLead?.status || 'Active'} • {selectedLead?.display_mobile || selectedLead?.mobile || 'No Number'}</Text>
                            </View>
                        </View>

                        <View style={styles.menuGrid}>
                            <TouchableOpacity style={styles.menuItem} onPress={() => {
                                if (!selectedLead) return;
                                setMenuVisible(false);
                                router.push({ pathname: '/lead-action', params: { leadId: selectedLead.id, leadName: selectedLead.name, autoAction: 'update' } });
                            }}>
                                <View style={[styles.menuIcon, { backgroundColor: '#eef2ff' }]}><Edit3 size={20} color="#6366f1" /></View>
                                <Text style={styles.menuLabel}>Edit Profile</Text>
                            </TouchableOpacity>

                            <TouchableOpacity style={styles.menuItem} onPress={() => {
                                if (!selectedLead) return;
                                setMenuVisible(false);
                                router.push({ pathname: '/lead-action', params: { leadId: selectedLead.id, leadName: selectedLead.name, autoAction: 'update', focusInteraction: 'true' } });
                            }}>
                                <View style={[styles.menuIcon, { backgroundColor: '#f5f3ff' }]}><MessageCircle size={20} color="#8b5cf6" /></View>
                                <Text style={styles.menuLabel}>Update Log</Text>
                            </TouchableOpacity>

                            {user?.role === 'admin' && (
                                <>
                                    <TouchableOpacity style={styles.menuItem} onPress={startAssignment}>
                                        <View style={[styles.menuIcon, { backgroundColor: '#fff7ed' }]}><UserPlus size={20} color="#f59e0b" /></View>
                                        <Text style={styles.menuLabel}>Assign Lead</Text>
                                    </TouchableOpacity>

                                    <TouchableOpacity style={styles.menuItem} onPress={handleDelete}>
                                        <View style={[styles.menuIcon, { backgroundColor: '#fef2f2' }]}><Trash2 size={20} color="#ef4444" /></View>
                                        <Text style={styles.menuLabel}>Delete Lead</Text>
                                    </TouchableOpacity>
                                </>
                            )}
                            
                            <TouchableOpacity style={[styles.menuItem, { width: '100%' }]} onPress={() => {
                                if (!selectedLead) return;
                                setMenuVisible(false);
                                router.push({ pathname: '/call-details', params: { mobile: selectedLead.mobile, name: selectedLead.name, leadId: selectedLead.id } });
                            }}>
                                <View style={[styles.menuIcon, { backgroundColor: '#f0fdf4', width: '100%' }]}><Activity size={20} color="#16a34a" /></View>
                                <Text style={styles.menuLabel}>Quick Activity & History</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </TouchableOpacity>
            </Modal>

            {/* Assignment Sub-Modal */}
            <Modal visible={assignModalVisible} transparent animationType="fade">
                <View style={styles.modalOverlayCenter}>
                    <View style={styles.assignBox}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Choose Executive</Text>
                            <TouchableOpacity onPress={() => setAssignModalVisible(false)}><X size={20} color="#94a3b8" /></TouchableOpacity>
                        </View>
                        <FlatList
                            data={executives}
                            renderItem={({ item }) => (
                                item && (
                                    <TouchableOpacity style={styles.execItem} onPress={() => completeAssignment(item.id)}>
                                        <User size={16} color="#64748b" />
                                        <Text style={styles.execName}>{item.name}</Text>
                                        <ChevronRight size={16} color="#cbd5e1" style={{ marginLeft: 'auto' }} />
                                    </TouchableOpacity>
                                )
                            )}
                            keyExtractor={item => item?.id?.toString() || Math.random().toString()}
                            ListEmptyComponent={<Text style={{ padding: 20, textAlign: 'center', color: '#94a3b8' }}>No active executives found.</Text>}
                        />
                    </View>
                </View>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#fcfcfe' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingVertical: 12 },
    headerDate: { fontSize: 9, fontWeight: '900', color: '#6366f1', letterSpacing: 1, marginBottom: 2 },
    title: { fontSize: 24, fontWeight: '900', color: '#0f172a' },
    compactAdd: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#6366f1', paddingHorizontal: 14, paddingVertical: 8, borderRadius: 12, gap: 6, shadowColor: '#6366f1', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.2, shadowRadius: 8, elevation: 4 },
    addText: { color: '#fff', fontSize: 13, fontWeight: '900' },
    searchContainer: { paddingHorizontal: 20, marginBottom: 8 },
    searchBar: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f1f5f9', paddingHorizontal: 14, height: 44, borderRadius: 14 },
    searchInput: { flex: 1, marginLeft: 10, fontSize: 14, fontWeight: '700', color: '#1e293b' },
    clearBtn: { backgroundColor: '#e2e8f0', width: 20, height: 20, borderRadius: 10, justifyContent: 'center', alignItems: 'center' },
    filterOuter: { marginBottom: 10 },
    filterStrip: { paddingHorizontal: 20, paddingVertical: 6, gap: 8 },
    filterPill: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 12, paddingVertical: 8, borderRadius: 10, backgroundColor: '#fff', borderWidth: 1, borderColor: '#f1f5f9', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 2, elevation: 1 },
    filterPillActive: { backgroundColor: '#6366f1', borderColor: '#6366f1' },
    filterText: { fontSize: 12, fontWeight: '800', color: '#64748b' },
    filterTextActive: { color: '#fff' },
    list: { paddingHorizontal: 16, paddingBottom: 100 },
    card: { backgroundColor: '#fff', borderRadius: 16, marginBottom: 10, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 4, elevation: 2, overflow: 'hidden', flexDirection: 'row' },
    sideIndicator: { width: 4, height: '100%' },
    cardContent: { flex: 1, flexDirection: 'row', alignItems: 'center', padding: 12 },
    avatar: { width: 42, height: 42, borderRadius: 12, backgroundColor: '#f5f3ff', justifyContent: 'center', alignItems: 'center', marginRight: 12 },
    avatarText: { fontSize: 16, fontWeight: '900', color: '#6366f1' },
    cardInfo: { flex: 1 },
    nameRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 4 },
    leadName: { fontSize: 15, fontWeight: '900', color: '#1e293b', flex: 1, marginRight: 8 },
    detailsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
    contactRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
    mobileText: { fontSize: 12, color: '#6366f1', fontWeight: '800', fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace' },
    locationRow: { flexDirection: 'row', alignItems: 'center', gap: 4, flex: 1 },
    locationText: { fontSize: 11, color: '#94a3b8', fontWeight: '700' },
    statusBadge: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6, borderWidth: 1 },
    statusText: { fontSize: 9, fontWeight: '900', textTransform: 'uppercase' },
    actions: { flexDirection: 'row', gap: 8 },
    actionBtn: { width: 34, height: 34, borderRadius: 10, justifyContent: 'center', alignItems: 'center' },

    // Modal Styles
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
    modalOverlayCenter: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', padding: 30 },
    modalContent: { backgroundColor: '#fff', borderTopLeftRadius: 30, borderTopRightRadius: 30, padding: 20, paddingBottom: Platform.OS === 'ios' ? 40 : 20 },
    modalHandle: { width: 40, height: 5, backgroundColor: '#e2e8f0', borderRadius: 10, alignSelf: 'center', marginBottom: 20 },
    modalHeader: { flexDirection: 'row', alignItems: 'center', gap: 14, marginBottom: 24 },
    modalAvatar: { width: 48, height: 48, borderRadius: 16, backgroundColor: '#f5f3ff', justifyContent: 'center', alignItems: 'center' },
    modalAvatarText: { fontSize: 18, fontWeight: '900', color: '#6366f1' },
    modalLeadName: { fontSize: 18, fontWeight: '900', color: '#1e293b' },
    modalLeadSub: { fontSize: 13, color: '#94a3b8', fontWeight: '600', marginTop: 2 },
    menuGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
    menuItem: { width: '48%', backgroundColor: '#fcfcfe', padding: 16, borderRadius: 20, alignItems: 'center', borderWidth: 1, borderColor: '#f1f5f9' },
    menuIcon: { width: 44, height: 44, borderRadius: 14, justifyContent: 'center', alignItems: 'center', marginBottom: 10 },
    menuLabel: { fontSize: 13, fontWeight: '800', color: '#1e293b' },

    assignBox: { backgroundColor: '#fff', borderRadius: 24, overflow: 'hidden', padding: 20, maxHeight: '60%' },
    modalTitle: { fontSize: 16, fontWeight: '900', color: '#0f172a', flex: 1 },
    execItem: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    execName: { fontSize: 15, fontWeight: '700', color: '#475569' }
});
