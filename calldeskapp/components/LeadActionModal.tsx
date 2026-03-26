import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator, TextInput, ScrollView, Modal, Platform, Dimensions } from 'react-native';
import { apiCall } from '../services/api';
import { User, Phone, StickyNote, Calendar as CalendarIcon, CheckCircle2, LayoutList, X } from 'lucide-react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useSnackbar } from '../context/SnackbarContext';

const { height: SCREEN_HEIGHT } = Dimensions.get('window');

interface LeadActionModalProps {
    visible: boolean;
    onClose: () => void;
    autoNumber: string;
    initialAction: 'add' | 'update';
    leadId?: string;
    leadName?: string;
}

export default function LeadActionModal({ visible, onClose, autoNumber, initialAction, leadId, leadName }: LeadActionModalProps) {
    const { showSnackbar } = useSnackbar();
    
    const [currentAction, setCurrentAction] = useState(initialAction);
    const [currentLeadId, setCurrentLeadId] = useState(leadId);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    
    // Metadata
    const [metadata, setMetadata] = useState<any>({ sources: [], statuses: [], custom_fields: [] });
    const [states, setStates] = useState<any[]>([]);
    const [districts, setDistricts] = useState<any[]>([]);
    const [blocks, setBlocks] = useState<any[]>([]);

    // Form fields
    const [name, setName] = useState('');
    const [mobile, setMobile] = useState('');
    const [alternateMobile, setAlternateMobile] = useState('');
    const [status, setStatus] = useState('New');
    const [remark, setRemark] = useState('');
    const [sourceId, setSourceId] = useState('0');
    const [nextFollowUp, setNextFollowUp] = useState('');
    const [showDatePicker, setShowDatePicker] = useState(false);
    const [stateId, setStateId] = useState('');
    const [districtId, setDistrictId] = useState('');
    const [blockId, setBlockId] = useState('');
    const [customValues, setCustomValues] = useState<any>({});

    useEffect(() => {
        if (!visible) return;
        
        const prepare = async () => {
            setLoading(true);
            setMobile(autoNumber || '');
            setCurrentAction(initialAction);
            setCurrentLeadId(leadId);

            try {
                const metaRes = await apiCall('leads.php?action=form_metadata', 'GET');
                if (metaRes.success) {
                    setMetadata(metaRes.data);
                    if (metaRes.data.statuses.length > 0) setStatus(metaRes.data.statuses[0].status_name);
                    if (metaRes.data.sources.length > 0) setSourceId(metaRes.data.sources[0].id.toString());
                }

                const statesRes = await apiCall('geography.php?type=states', 'GET');
                if (statesRes.success) setStates(statesRes.data);

                if (initialAction === 'add' && autoNumber) {
                    const checkRes = await apiCall(`leads.php?search=${autoNumber}`, 'GET');
                    if (checkRes.success && checkRes.data?.length > 0) {
                        const l = checkRes.data[0];
                        setCurrentAction('update');
                        setCurrentLeadId(l.id.toString());
                        setName(l.name);
                        setMobile(l.mobile);
                        setSourceId(l.source_id?.toString() || '0');
                    }
                } else if (initialAction === 'update') {
                    setName(leadName || '');
                }
            } catch (err) {
                console.error('Modal initialization error:', err);
            } finally {
                setLoading(false);
            }
        };
        prepare();
    }, [visible, autoNumber, initialAction, leadId, leadName]);

    const handleStateChange = async (id: string) => {
        setStateId(id); setDistrictId(''); setBlockId(''); setDistricts([]); setBlocks([]);
        const res = await apiCall(`geography.php?type=districts&state_id=${id}`, 'GET');
        if (res.success) setDistricts(res.data);
    };

    const handleDistrictChange = async (id: string) => {
        setDistrictId(id); setBlockId(''); setBlocks([]);
        const res = await apiCall(`geography.php?type=blocks&district_id=${id}`, 'GET');
        if (res.success) setBlocks(res.data);
    };

    const handleSave = async () => {
        if (!name) { showSnackbar('Please enter lead name', 'error'); return; }
        setSubmitting(true);
        try {
            const endpoint = currentAction === 'add' ? 'leads.php' : 'followups.php';
            const payload = currentAction === 'add' ? {
                name, mobile, alternate_mobile: alternateMobile, source_id: sourceId, status,
                remarks: remark || 'Added from mobile popup', state_id: stateId, district_id: districtId, block_id: blockId,
                custom_fields: JSON.stringify(customValues)
            } : {
                lead_id: currentLeadId, status, remark, next_follow_up_date: nextFollowUp,
            };

            const result = await apiCall(endpoint, 'POST', payload);
            if (result.success) {
                showSnackbar(currentAction === 'add' ? 'Lead created' : 'Status updated', 'success');
                onClose();
            } else {
                showSnackbar(result.message || 'Action failed', 'error');
            }
        } catch (e) {
            showSnackbar('Network error', 'error');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal visible={visible} animationType="slide" transparent>
            <View style={styles.overlay}>
                <View style={styles.modalBody}>
                    <View style={styles.fancyHeader}>
                        <View style={styles.indicator} />
                        <View style={styles.headerRow}>
                            <View style={styles.headerTextCol}>
                                <Text style={styles.headerTitle}>{currentAction === 'add' ? '✨ New Prospect' : '📝 Interaction Update'}</Text>
                                <Text style={styles.headerSubtitle}>{mobile}</Text>
                            </View>
                            <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
                                <X size={22} color="#64748b" />
                            </TouchableOpacity>
                        </View>
                    </View>

                    {loading ? (
                        <View style={styles.loaderContainer}>
                            <ActivityIndicator size="large" color="#6366f1" />
                            <Text style={styles.loaderText}>Syncing CRM context...</Text>
                        </View>
                    ) : (
                        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
                            {/* Personal Details */}
                            <Text style={styles.sectionLabel}>CONTACT INFO</Text>
                            <View style={styles.inputGroup}>
                                <View style={styles.inputContainer}>
                                    <User size={18} color="#6366f1" />
                                    <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Lead Name" placeholderTextColor="#94a3b8" />
                                </View>
                            </View>

                            {currentAction === 'add' && (
                                <View style={styles.inputGroup}>
                                    <View style={styles.inputContainer}>
                                        <Phone size={18} color="#6366f1" />
                                        <TextInput style={styles.input} value={alternateMobile} onChangeText={setAlternateMobile} placeholder="Alternate Number (WhatsApp)" placeholderTextColor="#94a3b8" keyboardType="phone-pad" />
                                    </View>
                                </View>
                            )}

                            {/* Status Pills */}
                            <Text style={[styles.sectionLabel, { marginTop: 12 }]}>DISPOSITION STATUS</Text>
                            <View style={styles.pillGrid}>
                                {metadata.statuses.map((s: any) => {
                                    const isActive = status === s.status_name;
                                    return (
                                        <TouchableOpacity key={s.id} onPress={() => setStatus(s.status_name)} 
                                            style={[styles.statusPill, isActive && { backgroundColor: s.color_code || '#6366f1', borderColor: s.color_code || '#6366f1' }]}>
                                            <Text style={[styles.statusPillText, isActive && { color: '#fff' }]}>{s.status_name}</Text>
                                        </TouchableOpacity>
                                    );
                                })}
                            </View>

                            {/* Location (Only for New) */}
                            {currentAction === 'add' && states.length > 0 && (
                                <>
                                    <Text style={[styles.sectionLabel, { marginTop: 20 }]}>GEOGRAPHY</Text>
                                    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.horizScroll}>
                                        {states.map(s => (
                                            <TouchableOpacity key={s.id} onPress={() => handleStateChange(s.id.toString())} 
                                                style={[styles.geoPill, stateId === s.id.toString() && styles.geoPillActive]}>
                                                <Text style={[styles.geoPillText, stateId === s.id.toString() && styles.geoPillTextActive]}>{s.name}</Text>
                                            </TouchableOpacity>
                                        ))}
                                    </ScrollView>
                                    
                                    {districts.length > 0 && (
                                        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.horizScroll}>
                                            {districts.map(d => (
                                                <TouchableOpacity key={d.id} onPress={() => handleDistrictChange(d.id.toString())} 
                                                    style={[styles.geoPill, districtId === d.id.toString() && { backgroundColor: '#9333ea', borderColor: '#9333ea' }]}>
                                                    <Text style={[styles.geoPillText, districtId === d.id.toString() && { color: '#fff' }]}>{d.name}</Text>
                                                </TouchableOpacity>
                                            ))}
                                        </ScrollView>
                                    )}
                                </>
                            )}

                            {/* Custom Fields (Simplified) */}
                            {currentAction === 'add' && metadata.custom_fields.length > 0 && (
                                <View style={{ marginTop: 12 }}>
                                    {metadata.custom_fields.filter((f:any) => f.field_type !== 'AUTO').slice(0, 3).map((f: any) => (
                                        <View key={f.id} style={styles.inputGroup}>
                                            <View style={styles.inputContainer}>
                                                <LayoutList size={18} color="#94a3b8" />
                                                <TextInput style={styles.input} placeholder={`Enter ${f.field_name}`} value={customValues[f.id] || ''} 
                                                    onChangeText={(v) => setCustomValues({...customValues, [f.id]: v})} />
                                            </View>
                                        </View>
                                    ))}
                                </View>
                            )}

                            {/* Remarks & Time */}
                            <Text style={[styles.sectionLabel, { marginTop: 12 }]}>CONVERSATION NOTES</Text>
                            <View style={[styles.inputContainer, styles.textAreaContainer]}>
                                <StickyNote size={18} color="#6366f1" style={{ marginTop: 2 }} />
                                <TextInput style={styles.textArea} value={remark} onChangeText={setRemark} placeholder="Type observation here..." multiline numberOfLines={2} placeholderTextColor="#94a3b8" />
                            </View>

                            <TouchableOpacity style={styles.dateRow} onPress={() => setShowDatePicker(true)}>
                                <CalendarIcon size={18} color="#6366f1" />
                                <Text style={styles.dateText}>{nextFollowUp ? `Next: ${nextFollowUp}` : 'Set Follow-up Date (Optional)'}</Text>
                            </TouchableOpacity>
                            {showDatePicker && (
                                <DateTimePicker value={nextFollowUp ? new Date(nextFollowUp) : new Date()} mode="date" display="default" minimumDate={new Date()}
                                    onChange={(e, d) => { setShowDatePicker(false); if (d) setNextFollowUp(d.toISOString().split('T')[0]); }} />
                            )}

                            <TouchableOpacity style={[styles.submitBtn, submitting && styles.disabledBtn]} onPress={handleSave} disabled={submitting}>
                                {submitting ? <ActivityIndicator color="#fff" /> : <><CheckCircle2 size={22} color="#fff" /><Text style={styles.submitBtnText}>{currentAction === 'add' ? 'SAVE NEW PROSPECT' : 'LOG CONVERSATION'}</Text></>}
                            </TouchableOpacity>
                        </ScrollView>
                    )}
                </View>
            </View>
        </Modal>
    );
}

const styles = StyleSheet.create({
    overlay: { flex: 1, backgroundColor: 'rgba(15, 23, 42, 0.75)', justifyContent: 'flex-end' },
    modalBody: { backgroundColor: '#fff', borderTopLeftRadius: 32, borderTopRightRadius: 32, height: SCREEN_HEIGHT * 0.85, shadowColor: '#000', shadowOffset: { width: 0, height: -10 }, shadowOpacity: 0.2, shadowRadius: 20, elevation: 20 },
    fancyHeader: { paddingHorizontal: 24, paddingTop: 12, paddingBottom: 20, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    indicator: { width: 40, height: 4, backgroundColor: '#e2e8f0', borderRadius: 2, alignSelf: 'center', marginBottom: 16 },
    headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    headerTextCol: { flex: 1 },
    headerTitle: { fontSize: 20, fontWeight: '900', color: '#1e293b', letterSpacing: -0.5 },
    headerSubtitle: { fontSize: 13, color: '#6366f1', fontWeight: '800', marginTop: 2 },
    closeBtn: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#f1f5f9', justifyContent: 'center', alignItems: 'center' },
    scrollContent: { padding: 24 },
    sectionLabel: { fontSize: 11, fontWeight: '900', color: '#94a3b8', letterSpacing: 1.5, marginBottom: 12, textTransform: 'uppercase' },
    inputGroup: { marginBottom: 12 },
    inputContainer: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f8fafc', borderRadius: 16, paddingHorizontal: 16, height: 56, borderWidth: 1.5, borderColor: '#f1f5f9' },
    textAreaContainer: { height: 90, alignItems: 'flex-start', paddingTop: 16 },
    input: { flex: 1, height: '100%', marginLeft: 12, fontSize: 16, color: '#1e293b', fontWeight: '700' },
    textArea: { flex: 1, height: '100%', marginLeft: 12, fontSize: 15, color: '#1e293b', fontWeight: '700', textAlignVertical: 'top' },
    pillGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 16 },
    statusPill: { paddingHorizontal: 14, paddingVertical: 10, borderRadius: 12, borderWidth: 1.5, borderColor: '#e2e8f0', backgroundColor: '#fff' },
    statusPillText: { fontSize: 12, fontWeight: '800', color: '#64748b' },
    horizScroll: { flexDirection: 'row', marginBottom: 10 },
    geoPill: { paddingHorizontal: 16, paddingVertical: 9, borderRadius: 12, backgroundColor: '#eff6ff', marginRight: 10, borderWidth: 1.5, borderColor: '#dbeafe' },
    geoPillActive: { backgroundColor: '#3b82f6', borderColor: '#3b82f6' },
    geoPillText: { fontSize: 13, fontWeight: '800', color: '#3b82f6' },
    geoPillTextActive: { color: '#fff' },
    dateRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#f0f9ff', borderRadius: 16, height: 56, paddingHorizontal: 16, marginTop: 12, borderWidth: 1.5, borderColor: '#e0f2fe' },
    dateText: { marginLeft: 12, fontSize: 14, color: '#0369a1', fontWeight: '800' },
    submitBtn: { backgroundColor: '#6366f1', height: 60, borderRadius: 18, marginTop: 32, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 12, shadowColor: '#6366f1', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 8 },
    submitBtnText: { color: '#fff', fontSize: 16, fontWeight: '900', letterSpacing: 0.5 },
    loaderContainer: { padding: 40, alignItems: 'center' },
    loaderText: { marginTop: 16, fontSize: 14, color: '#94a3b8', fontWeight: '700' },
    disabledBtn: { opacity: 0.6 }
});
