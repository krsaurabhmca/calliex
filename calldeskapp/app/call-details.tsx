import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator, ScrollView, FlatList, TextInput, KeyboardAvoidingView, Platform } from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { apiCall } from '../services/api';
import { ArrowLeft, Phone, User, Calendar as CalendarIcon, StickyNote, Play, History, CheckCircle, ChevronDown, CheckCircle2 } from 'lucide-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useSnackbar } from '../context/SnackbarContext';
import DateTimePicker from '@react-native-community/datetimepicker';

export default function CallDetailsScreen() {
    const params = useLocalSearchParams();
    const { mobile: rawMobile, name: initialName, leadId: initialLeadId } = params;
    const router = useRouter();
    const insets = useSafeAreaInsets();
    const { showSnackbar } = useSnackbar();

    const [mobile, setMobile] = useState('');
    const [name, setName] = useState(initialName as string || 'Unknown');
    const [leadId, setLeadId] = useState(initialLeadId as string || '');
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState<'Update' | 'History' | 'Notes' | 'Recordings'>('Update');

    // Data states
    const [historyLogs, setHistoryLogs] = useState<any[]>([]);
    const [notes, setNotes] = useState<any[]>([]);
    const [recordings, setRecordings] = useState<any[]>([]);
    const [statuses, setStatuses] = useState<any[]>([]);
    
    // Form states
    const [updateStatus, setUpdateStatus] = useState('New');
    const [updateRemark, setUpdateRemark] = useState('');
    const [nextFollowUp, setNextFollowUp] = useState('');
    const [showDatePicker, setShowDatePicker] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        let clean = (rawMobile as string || '').replace(/[^0-9]/g, '');
        if (clean.length > 10) clean = clean.slice(-10);
        setMobile(clean);

        const fetchData = async () => {
            setLoading(true);
            
            // 1. Fetch Lead Info/Metadata
            const metaRes = await apiCall('leads.php?action=form_metadata');
            if (metaRes.success) {
                setStatuses(metaRes.data.statuses || []);
            }

            // 2. Fetch History (Calls)
            const historyRes = await apiCall(`call_logs.php?search=${clean}`);
            if (historyRes.success) {
                setHistoryLogs(historyRes.data.logs || []);
                // If we don't have a lead ID but history has one, set it
                const foundLead = historyRes.data.logs?.find((l: any) => l.lead_id);
                if (foundLead) {
                    setLeadId(foundLead.lead_id);
                    if (!initialName) setName(foundLead.lead_name || foundLead.caller_name || 'Prospect');
                    setUpdateStatus(foundLead.lead_status || 'New');
                }
            }

            // 3. Fetch Notes (Interaction History)
            const notesRes = await apiCall(`followups.php?mobile=${clean}`);
            if (notesRes.success) {
                setNotes(notesRes.data || []);
            }

            // 4. Fetch Recordings
            const recsRes = await apiCall(`call_logs.php?action=recordings&mobile=${clean}`);
            if (recsRes.success) {
                setRecordings(recsRes.data.recordings || []);
            }

            setLoading(false);
        };

        if (clean) fetchData();
    }, [rawMobile]);

    const handleUpdate = async () => {
        if (!leadId) {
            showSnackbar('Please add this number as a lead first', 'error');
            return;
        }
        if (!updateRemark) {
            showSnackbar('Please enter a remark', 'error');
            return;
        }

        setSubmitting(true);
        const res = await apiCall('followups.php', 'POST', {
            lead_id: leadId,
            status: updateStatus,
            remark: updateRemark,
            next_follow_up_date: nextFollowUp,
        });

        if (res.success) {
            showSnackbar('Interaction Recorded ✅', 'success');
            setUpdateRemark('');
            // Refresh notes
            const notesRes = await apiCall(`followups.php?mobile=${mobile}`);
            if (notesRes.success) setNotes(notesRes.data || []);
        } else {
            showSnackbar(res.message || 'Update failed', 'error');
        }
        setSubmitting(false);
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    };

    if (loading) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
                <Text style={{ marginTop: 12, color: '#94a3b8', fontWeight: '800', fontSize: 13 }}>GATHERING INTEL...</Text>
            </View>
        );
    }

    return (
        <View style={[styles.container, { paddingTop: insets.top, paddingBottom: insets.bottom }]}>
            <Stack.Screen options={{ headerShown: false }} />
            
            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
                    <ArrowLeft size={22} color="#1e293b" />
                </TouchableOpacity>
                <View style={styles.headerInfo}>
                    <Text style={styles.headerTitle}>{name}</Text>
                    <View style={styles.headerSub}>
                        <Phone size={12} color="#6366f1" />
                        <Text style={styles.headerMobile}>{mobile}</Text>
                        <View style={styles.dot} />
                        <Text style={styles.headerLeadStatus}>{leadId ? 'LOADED LEAD' : 'UNASSIGNED'}</Text>
                    </View>
                </View>
                {leadId ? (
                    <View style={styles.statusBadge}>
                        <CheckCircle2 size={14} color="#10b981" />
                        <Text style={styles.statusBadgeText}>{updateStatus}</Text>
                    </View>
                ) : (
                    <TouchableOpacity style={styles.addLeadBtn} onPress={() => router.push({ pathname: '/lead-action', params: { autoAction: 'add', autoNumber: mobile } })}>
                        <Text style={styles.addLeadBtnText}>ADD</Text>
                    </TouchableOpacity>
                )}
            </View>

            {/* Tabs */}
            <View style={styles.tabBar}>
                <TouchableOpacity style={[styles.tabItem, activeTab === 'Update' && styles.tabItemActive]} onPress={() => setActiveTab('Update')}>
                    <StickyNote size={18} color={activeTab === 'Update' ? '#6366f1' : '#94a3b8'} />
                    <Text style={[styles.tabText, activeTab === 'Update' && styles.tabTextActive]}>Log</Text>
                </TouchableOpacity>
                <TouchableOpacity style={[styles.tabItem, activeTab === 'History' && styles.tabItemActive]} onPress={() => setActiveTab('History')}>
                    <History size={18} color={activeTab === 'History' ? '#6366f1' : '#94a3b8'} />
                    <Text style={[styles.tabText, activeTab === 'History' && styles.tabTextActive]}>Calls</Text>
                </TouchableOpacity>
                <TouchableOpacity style={[styles.tabItem, activeTab === 'Notes' && styles.tabItemActive]} onPress={() => setActiveTab('Notes')}>
                    <View style={styles.tabIconWithBadge}>
                        <StickyNote size={18} color={activeTab === 'Notes' ? '#6366f1' : '#94a3b8'} />
                        {notes.length > 0 && <View style={styles.miniBadge}><Text style={styles.miniBadgeText}>{notes.length}</Text></View>}
                    </View>
                    <Text style={[styles.tabText, activeTab === 'Notes' && styles.tabTextActive]}>Notes</Text>
                </TouchableOpacity>
                <TouchableOpacity style={[styles.tabItem, activeTab === 'Recordings' && styles.tabItemActive]} onPress={() => setActiveTab('Recordings')}>
                    <Play size={18} color={activeTab === 'Recordings' ? '#6366f1' : '#94a3b8'} />
                    <Text style={[styles.tabText, activeTab === 'Recordings' && styles.tabTextActive]}>Audio</Text>
                </TouchableOpacity>
            </View>

            <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
                {activeTab === 'Update' ? (
                    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
                        <View style={styles.tabContent}>
                            <Text style={styles.sectionLabel}>UPDATE PROGRESS</Text>
                            
                            <Text style={styles.label}>LEAD STAGE</Text>
                            <View style={styles.statusGrid}>
                                {statuses.map((s) => (
                                    <TouchableOpacity 
                                        key={s.id} 
                                        style={[styles.statusToggle, updateStatus === s.status_name && { backgroundColor: s.color_code || '#6366f1', borderColor: s.color_code || '#6366f1' }]}
                                        onPress={() => setUpdateStatus(s.status_name)}
                                    >
                                        <Text style={[styles.statusToggleText, updateStatus === s.status_name && { color: '#fff' }]}>{s.status_name}</Text>
                                    </TouchableOpacity>
                                ))}
                            </View>

                            <Text style={styles.label}>ACTIVITY REMARK</Text>
                            <View style={styles.inputContainer}>
                                <TextInput 
                                    style={styles.textArea} 
                                    placeholder="Brief summary of interaction..." 
                                    multiline 
                                    numberOfLines={4}
                                    value={updateRemark}
                                    onChangeText={setUpdateRemark}
                                    placeholderTextColor="#cbd5e1"
                                />
                            </View>

                            <Text style={styles.label}>NEXT TOUCHPOINT</Text>
                            <TouchableOpacity style={styles.dateSelector} onPress={() => setShowDatePicker(true)}>
                                <CalendarIcon size={18} color="#6366f1" />
                                <Text style={styles.dateSelectorText}>{nextFollowUp || 'Assign follow-up date'}</Text>
                                <ChevronDown size={16} color="#94a3b8" />
                            </TouchableOpacity>

                            {showDatePicker && (
                                <DateTimePicker 
                                    value={nextFollowUp ? new Date(nextFollowUp) : new Date()} 
                                    mode="date" 
                                    display="default" 
                                    minimumDate={new Date()}
                                    onChange={(e, d) => { setShowDatePicker(false); if (d) setNextFollowUp(d.toISOString().split('T')[0]); }} 
                                />
                            )}

                            <TouchableOpacity 
                                style={[styles.submitBtn, submitting && { opacity: 0.7 }]} 
                                onPress={handleUpdate}
                                disabled={submitting}
                            >
                                {submitting ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitBtnText}>RECORD INTERACTION</Text>}
                            </TouchableOpacity>
                        </View>
                    </KeyboardAvoidingView>
                ) : activeTab === 'History' ? (
                    <View style={styles.tabContent}>
                        {historyLogs.length > 0 ? historyLogs.map((log) => (
                            <View key={log.id} style={styles.historyCard}>
                                <View style={[styles.typeLine, { backgroundColor: log.type === 'Missed' ? '#ef4444' : log.type === 'Incoming' ? '#10b981' : '#6366f1' }]} />
                                <View style={styles.historyCardMain}>
                                    <View style={styles.historyHeader}>
                                        <Text style={styles.historyType}>{log.type.toUpperCase()}</Text>
                                        <Text style={styles.historyTime}>{formatDate(log.call_time)}</Text>
                                    </View>
                                    <View style={styles.historyBody}>
                                        <Text style={styles.durationText}>{Math.floor(log.duration / 60)}m {log.duration % 60}s</Text>
                                        {log.recording_path && <Play size={14} color="#6366f1" />}
                                    </View>
                                </View>
                            </View>
                        )) : (
                            <View style={styles.emptyState}>
                                <History size={48} color="#f1f5f9" />
                                <Text style={styles.emptyText}>No call history found</Text>
                            </View>
                        )}
                    </View>
                ) : activeTab === 'Notes' ? (
                    <View style={styles.tabContent}>
                        {notes.length > 0 ? notes.map((note) => (
                            <View key={note.id} style={styles.noteCard}>
                                <View style={styles.noteHeader}>
                                    <Text style={styles.noteAuthor}>{note.executive_name || 'System'}</Text>
                                    <Text style={styles.noteDate}>{formatDate(note.created_at)}</Text>
                                </View>
                                <Text style={styles.noteText}>{note.remark}</Text>
                                {note.next_follow_up_date && (
                                    <View style={styles.nextTouch}>
                                        <CalendarIcon size={12} color="#6366f1" />
                                        <Text style={styles.nextTouchText}>Scheduled: {formatDate(note.next_follow_up_date)}</Text>
                                    </View>
                                )}
                            </View>
                        )) : (
                            <View style={styles.emptyState}>
                                <StickyNote size={48} color="#f1f5f9" />
                                <Text style={styles.emptyText}>No interaction logs yet</Text>
                            </View>
                        )}
                    </View>
                ) : (
                    <View style={styles.tabContent}>
                        {recordings.length > 0 ? recordings.map((rec) => (
                            <TouchableOpacity key={rec.id} style={styles.recCard}>
                                <View style={styles.recIcon}><Play size={18} color="#6366f1" /></View>
                                <View style={styles.recInfo}>
                                    <Text style={styles.recName}>{rec.mobile} Recording</Text>
                                    <Text style={styles.recMeta}>{formatDate(rec.call_time)} • {rec.duration}s</Text>
                                </View>
                                <ChevronDown size={20} color="#cbd5e1" style={{ transform: [{rotate: '-90deg'}] }} />
                            </TouchableOpacity>
                        )) : (
                            <View style={styles.emptyState}>
                                <Play size={48} color="#f1f5f9" />
                                <Text style={styles.emptyText}>No recording files available</Text>
                            </View>
                        )}
                    </View>
                )}
                <View style={{ height: 40 }} />
            </ScrollView>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#fcfcfe' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    header: { flexDirection: 'row', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    backBtn: { width: 44, height: 44, borderRadius: 18, backgroundColor: '#f1f5f9', justifyContent: 'center', alignItems: 'center', marginRight: 16 },
    headerInfo: { flex: 1 },
    headerTitle: { fontSize: 20, fontWeight: '900', color: '#0f172a' },
    headerSub: { flexDirection: 'row', alignItems: 'center', marginTop: 4, gap: 6 },
    headerMobile: { fontSize: 13, fontWeight: '700', color: '#6366f1' },
    dot: { width: 3, height: 3, borderRadius: 1.5, backgroundColor: '#cbd5e1' },
    headerLeadStatus: { fontSize: 10, fontWeight: '800', color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 0.5 },
    statusBadge: { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#f0fdf4', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 14, borderWidth: 1, borderColor: '#dcfce7' },
    statusBadgeText: { fontSize: 12, fontWeight: '800', color: '#16a34a' },
    addLeadBtn: { backgroundColor: '#6366f1', paddingHorizontal: 16, paddingVertical: 10, borderRadius: 14 },
    addLeadBtnText: { color: '#fff', fontSize: 12, fontWeight: '900' },
    tabBar: { flexDirection: 'row', backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f8fafc', paddingHorizontal: 10 },
    tabItem: { flex: 1, alignItems: 'center', paddingVertical: 16, gap: 6, borderBottomWidth: 3, borderBottomColor: 'transparent' },
    tabItemActive: { borderBottomColor: '#6366f1' },
    tabText: { fontSize: 11, fontWeight: '900', color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 0.5 },
    tabTextActive: { color: '#6366f1' },
    tabIconWithBadge: { position: 'relative' },
    miniBadge: { position: 'absolute', top: -6, right: -8, backgroundColor: '#ef4444', minWidth: 14, height: 14, borderRadius: 7, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 4 },
    miniBadgeText: { color: '#fff', fontSize: 8, fontWeight: '900' },
    content: { flex: 1 },
    tabContent: { padding: 20 },
    sectionLabel: { fontSize: 10, fontWeight: '900', color: '#6366f1', letterSpacing: 1.5, marginBottom: 24, textAlign: 'center' },
    label: { fontSize: 11, fontWeight: '800', color: '#94a3b8', marginBottom: 10, textTransform: 'uppercase', letterSpacing: 1 },
    statusGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 24 },
    statusToggle: { paddingHorizontal: 16, paddingVertical: 10, borderRadius: 12, backgroundColor: '#f1f5f9', borderWidth: 1.5, borderColor: '#f1f5f9' },
    statusToggleText: { fontSize: 13, fontWeight: '800', color: '#64748b' },
    inputContainer: { backgroundColor: '#f8fafc', borderRadius: 16, padding: 16, borderWidth: 1.5, borderColor: '#f1f5f9', marginBottom: 24 },
    textArea: { height: 100, fontSize: 15, color: '#1e293b', fontWeight: '700', textAlignVertical: 'top' },
    dateSelector: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f8fafc', borderRadius: 16, paddingHorizontal: 16, height: 56, borderWidth: 1.5, borderColor: '#f1f5f9', marginBottom: 32 },
    dateSelectorText: { flex: 1, marginLeft: 12, fontSize: 15, fontWeight: '700', color: '#1e293b' },
    submitBtn: { backgroundColor: '#6366f1', height: 60, borderRadius: 18, justifyContent: 'center', alignItems: 'center', shadowColor: '#6366f1', shadowOffset: { width: 0, height: 8 }, shadowOpacity: 0.25, shadowRadius: 12, elevation: 6 },
    submitBtnText: { color: '#fff', fontSize: 16, fontWeight: '900', letterSpacing: 1 },
    historyCard: { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 16, marginBottom: 12, overflow: 'hidden', borderWidth: 1, borderColor: '#f1f5f9' },
    typeLine: { width: 4 },
    historyCardMain: { flex: 1, padding: 14 },
    historyHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
    historyType: { fontSize: 10, fontWeight: '900', color: '#94a3b8' },
    historyTime: { fontSize: 11, fontWeight: '700', color: '#1e293b' },
    historyBody: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    durationText: { fontSize: 14, fontWeight: '800', color: '#1e293b' },
    noteCard: { backgroundColor: '#fff', borderRadius: 16, padding: 16, marginBottom: 12, borderWidth: 1, borderColor: '#f1f5f9' },
    noteHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10 },
    noteAuthor: { fontSize: 13, fontWeight: '900', color: '#1e293b' },
    noteDate: { fontSize: 11, color: '#94a3b8', fontWeight: '700' },
    noteText: { fontSize: 14, color: '#475569', lineHeight: 20, fontWeight: '600' },
    nextTouch: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 12, backgroundColor: '#f5f3ff', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 8, alignSelf: 'flex-start' },
    nextTouchText: { fontSize: 11, fontWeight: '800', color: '#6366f1' },
    recCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', padding: 16, borderRadius: 16, marginBottom: 12, borderWidth: 1, borderColor: '#f1f5f9' },
    recIcon: { width: 40, height: 40, borderRadius: 12, backgroundColor: '#f5f3ff', justifyContent: 'center', alignItems: 'center', marginRight: 14 },
    recInfo: { flex: 1 },
    recName: { fontSize: 14, fontWeight: '800', color: '#1e293b' },
    recMeta: { fontSize: 11, color: '#94a3b8', fontWeight: '700', marginTop: 2 },
    emptyState: { alignItems: 'center', justifyContent: 'center', paddingVertical: 60, opacity: 0.5 },
    emptyText: { marginTop: 16, fontSize: 14, fontWeight: '800', color: '#94a3b8' }
});
