import axios from "axios";
import React, { useMemo, useState, useCallback } from "react";
import { useForm } from "@inertiajs/react";
import { format } from "date-fns";
import { toast } from "sonner";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Combobox } from "@/components/ui/combobox";
import { Badge } from "@/components/ui/badge";
import { DatePicker, MultiDatePicker } from "@/components/ui/date-picker";
import { Label } from "@/components/ui/label";
import { X, Stethoscope, ChevronLeft } from "lucide-react";
import { FieldRow } from "./components/FieldRow";
import { ShiftSelect } from "./components/ShiftSelect";

// ─── Helpers ──────────────────────────────────────────────────────────────────

/** Parse "YYYY-MM-DD" → local-time Date (avoids UTC midnight offset issues). */
export function parseDate(str) {
    if (!str) return undefined;
    const [y, m, d] = str.split("-").map(Number);
    return new Date(y, m - 1, d);
}

function sdhDateLabel(recId, recById) {
    const label = (recById[recId]?.rec_label ?? "").toLowerCase();
    if (label.includes("hospital"))  return "Date sent to hospital";
    if (label.includes("sent home")) return "Date when employee was sent home";
    return "Date";
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function CreateFtw({ recommendations, canSelectEmployee }) {
    // Derive rec IDs from backend data
    const recById = useMemo(
        () => Object.fromEntries(recommendations.map((r) => [r.rec_id, r])),
        [recommendations],
    );

    const fitToWorkId = useMemo(
        () => recommendations.find((r) => r.rec_label.toLowerCase().includes("fit to work"))?.rec_id,
        [recommendations],
    );

    const restId = useMemo(
        () => recommendations.find((r) => r.rec_label.toLowerCase().includes("rest"))?.rec_id,
        [recommendations],
    );

    // Sent Home + Hospital — have Time Out (sdh_time)
    const sdhWithTimeIds = useMemo(
        () =>
            recommendations
                .filter((r) => ["sent home", "hospital"].some((kw) => r.rec_label.toLowerCase().includes(kw)))
                .map((r) => r.rec_id),
        [recommendations],
    );

    const unfitId = useMemo(
        () => recommendations.find((r) => r.rec_label.toLowerCase().includes("unfit"))?.rec_id,
        [recommendations],
    );

    // Employee cache populated as results load from the combobox
    const [empCache, setEmpCache] = useState({});

    const loadEmployees = useCallback(async (search, page) => {
        const { data: json } = await axios.get(route("ftw.employees"), {
            params: { search, page },
        });
        const list = json.data ?? [];

        setEmpCache((prev) => ({
            ...prev,
            ...Object.fromEntries(list.map((e) => [String(e.employid), e])),
        }));

        return {
            options: list.map((e) => ({
                value: String(e.employid),
                label: `${e.employid} – ${e.emp_name}`,
            })),
            hasMore: !!json.hasMore,
        };
    }, []);

    // Form state (dates stored as "YYYY-MM-DD" strings)
    const INITIAL = {
        emp_no: "",
        emp_name: "",
        emp_dept: "",
        emp_team: "",
        recommendation: "",
        emp_time_in: "",
        emp_diagnose: "",
        emp_shift: "",
        absence_dates: [],
        absent_count: 0,
        ftw_file: null,
        sdh_date: "",
        sdh_time: "",
        remarks: "",
        rest_date: "",
        rest_time_in: "",
    };

    const { data, setData, post, processing, errors, reset } = useForm(INITIAL);

    const recId       = data.recommendation ? parseInt(data.recommendation) : null;
    const selectedRec = recId ? recById[recId] : null;

    const isFitToWork = recId === fitToWorkId;
    const isSdh       = sdhWithTimeIds.includes(recId);
    const isUnfit     = recId === unfitId;
    const isRest      = recId === restId;

    // Disable weekends in the absence date picker when shift = Normal (3)
    const weekendDisabled = useMemo(
        () => (data.emp_shift === "3" ? [{ dayOfWeek: [0, 6] }] : []),
        [data.emp_shift],
    );

    const absenceDateObjects = useMemo(
        () => data.absence_dates.map(parseDate).filter(Boolean),
        [data.absence_dates],
    );

    // ── Handlers ──────────────────────────────────────────────────────────────

    function handleEmployeeChange(val) {
        const emp = val ? (empCache[val] ?? null) : null;
        setData({
            ...data,
            emp_no:   val ?? "",
            emp_name: emp?.emp_name   ?? "",
            emp_dept: emp?.department ?? "",
            emp_team: emp?.team       ?? "",
        });
    }

    function handleRecommendationChange(val) {
        setData({
            ...data,
            recommendation: val,
            emp_time_in: "",
            emp_diagnose: "",
            emp_shift: "",
            absence_dates: [],
            absent_count: 0,
            ftw_file: null,
            sdh_date: "",
            sdh_time: "",
            remarks: "",
            rest_date: "",
            rest_time_in: "",
        });
    }

    function handleShiftChange(val) {
        const filtered =
            val === "3"
                ? data.absence_dates.filter((s) => {
                      const d = parseDate(s);
                      return d && d.getDay() !== 0 && d.getDay() !== 6;
                  })
                : data.absence_dates;
        setData({ ...data, emp_shift: val, absence_dates: filtered, absent_count: filtered.length });
    }

    function handleAbsenceDatesChange(dates) {
        const strs = (dates ?? []).map((d) => format(d, "yyyy-MM-dd"));
        setData({ ...data, absence_dates: strs, absent_count: strs.length });
    }

    function removeAbsenceDate(str) {
        const next = data.absence_dates.filter((s) => s !== str);
        setData({ ...data, absence_dates: next, absent_count: next.length });
    }

    function handleSubmit(e) {
        e.preventDefault();
        post(route("ftw.store"), {
            forceFormData: true,
            onSuccess: () => {
                toast.success("FTW record saved successfully.");
                reset();
            },
        });
    }

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <AuthenticatedLayout>
            <div className="space-y-5 pb-10">
                {/* Page header */}
                <div className="flex items-center gap-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="shrink-0"
                        onClick={() => window.history.back()}
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <div className="rounded-lg bg-primary/10 p-2">
                        <Stethoscope className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-lg font-semibold leading-tight">New FTW Record</h1>
                        <p className="text-sm text-muted-foreground">Create a new Fit to Work record</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        {/* Card: Basic information */}
                        <div className="rounded-xl border bg-card shadow-sm">
                            <div className="px-5 py-3 border-b">
                                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                    Basic Information
                                </p>
                            </div>
                            <div className="px-5 py-4 space-y-4">
                                {canSelectEmployee && (
                                    <>
                                        <div className="grid grid-cols-2 gap-4">
                                            <FieldRow label="Employee" error={errors.emp_no}>
                                                <Combobox
                                                    placeholder="Search employee…"
                                                    value={data.emp_no}
                                                    onChange={handleEmployeeChange}
                                                    loadOptions={loadEmployees}
                                                    getDisplayValue={(opt) => opt.value}
                                                    clearable
                                                />
                                            </FieldRow>
                                            <FieldRow label="Employee Name">
                                                <Input
                                                    readOnly
                                                    value={data.emp_name}
                                                    placeholder="—"
                                                    className="bg-muted/50 cursor-default"
                                                />
                                            </FieldRow>
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <FieldRow label="Department">
                                                <Input
                                                    readOnly
                                                    value={data.emp_dept}
                                                    placeholder="—"
                                                    className="bg-muted/50 cursor-default"
                                                />
                                            </FieldRow>
                                            <FieldRow label="Team">
                                                <Input
                                                    readOnly
                                                    value={data.emp_team}
                                                    placeholder="—"
                                                    className="bg-muted/50 cursor-default"
                                                />
                                            </FieldRow>
                                        </div>
                                    </>
                                )}

                                <FieldRow label="Recommendation" error={errors.recommendation}>
                                    <Select
                                        value={data.recommendation}
                                        onValueChange={handleRecommendationChange}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select recommendation…" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {recommendations.map((r) => (
                                                <SelectItem key={r.rec_id} value={String(r.rec_id)}>
                                                    {r.rec_label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </FieldRow>
                            </div>
                        </div>

                        {/* Card: Dynamic fields per recommendation type */}
                        {recId && (
                            <div className="rounded-xl border bg-card shadow-sm">
                                <div className="px-5 py-3 border-b flex items-center justify-between">
                                    <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                        Details
                                    </p>
                                    <Badge variant="secondary" className="text-xs">
                                        {selectedRec?.rec_label}
                                    </Badge>
                                </div>
                                <div className="px-5 py-4 space-y-4">
                                    {/* Fit to Work */}
                                    {isFitToWork && (
                                        <>
                                            <div className="grid grid-cols-2 gap-4">
                                                <FieldRow label="Time In" error={errors.emp_time_in}>
                                                    <Input
                                                        type="time"
                                                        value={data.emp_time_in}
                                                        onChange={(e) => setData("emp_time_in", e.target.value)}
                                                    />
                                                </FieldRow>
                                                <FieldRow label="Shift" error={errors.emp_shift}>
                                                    <ShiftSelect value={data.emp_shift} onChange={handleShiftChange} />
                                                </FieldRow>
                                            </div>

                                            <FieldRow label="Diagnosis Details" error={errors.emp_diagnose}>
                                                <Textarea
                                                    placeholder="Enter diagnosis details…"
                                                    rows={3}
                                                    value={data.emp_diagnose}
                                                    onChange={(e) => setData("emp_diagnose", e.target.value)}
                                                    className="resize-none"
                                                />
                                            </FieldRow>

                                            <div className="space-y-2">
                                                <Label className="text-sm">Date of Absences</Label>
                                                <MultiDatePicker
                                                    value={absenceDateObjects}
                                                    onChange={handleAbsenceDatesChange}
                                                    placeholder="Select absence dates…"
                                                    disabled={weekendDisabled}
                                                />
                                                {data.absence_dates.length > 0 && (
                                                    <div className="flex flex-wrap gap-1.5 pt-1">
                                                        {[...data.absence_dates].sort().map((s) => (
                                                            <Badge
                                                                key={s}
                                                                variant="secondary"
                                                                className="gap-1 pl-2.5 pr-1 text-xs"
                                                            >
                                                                {format(parseDate(s), "MMM d, yyyy")}
                                                                <button
                                                                    type="button"
                                                                    onClick={() => removeAbsenceDate(s)}
                                                                    className="ml-0.5 rounded p-0.5 hover:bg-muted-foreground/20 transition-colors"
                                                                >
                                                                    <X className="h-3 w-3" />
                                                                </button>
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-2 gap-4">
                                                <FieldRow label="Total Days of Absence">
                                                    <Input
                                                        readOnly
                                                        value={data.absent_count}
                                                        className="bg-muted/50 cursor-default"
                                                    />
                                                </FieldRow>
                                                <FieldRow label="Attached File" error={errors.ftw_file}>
                                                    <Input
                                                        type="file"
                                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                        onChange={(e) =>
                                                            setData("ftw_file", e.target.files?.[0] ?? null)
                                                        }
                                                        className="cursor-pointer file:mr-3 file:rounded file:border-0 file:bg-primary/10 file:px-3 file:py-1 file:text-xs file:font-medium file:text-primary hover:file:bg-primary/20"
                                                    />
                                                </FieldRow>
                                            </div>
                                        </>
                                    )}

                                    {/* Sent Home / Send to Hospital */}
                                    {isSdh && (
                                        <>
                                            <FieldRow label="Diagnosis Details" error={errors.emp_diagnose}>
                                                <Textarea
                                                    placeholder="Enter diagnosis details…"
                                                    rows={3}
                                                    value={data.emp_diagnose}
                                                    onChange={(e) => setData("emp_diagnose", e.target.value)}
                                                    className="resize-none"
                                                />
                                            </FieldRow>

                                            <div className="grid grid-cols-3 gap-4">
                                                <FieldRow label="Day Shift" error={errors.emp_shift}>
                                                    <ShiftSelect
                                                        value={data.emp_shift}
                                                        onChange={(val) => setData("emp_shift", val)}
                                                    />
                                                </FieldRow>
                                                <FieldRow label={sdhDateLabel(recId, recById)} error={errors.sdh_date}>
                                                    <DatePicker
                                                        value={parseDate(data.sdh_date)}
                                                        onChange={(d) =>
                                                            setData("sdh_date", d ? format(d, "yyyy-MM-dd") : "")
                                                        }
                                                        placeholder="Select date…"
                                                    />
                                                </FieldRow>
                                                <FieldRow label="Time Out" error={errors.sdh_time}>
                                                    <Input
                                                        type="time"
                                                        value={data.sdh_time}
                                                        onChange={(e) => setData("sdh_time", e.target.value)}
                                                    />
                                                </FieldRow>
                                            </div>
                                        </>
                                    )}

                                    {/* Unfit to Work */}
                                    {isUnfit && (
                                        <>
                                            <FieldRow label="Remarks" error={errors.remarks}>
                                                <Textarea
                                                    placeholder="Enter remarks…"
                                                    rows={3}
                                                    value={data.remarks}
                                                    onChange={(e) => setData("remarks", e.target.value)}
                                                    className="resize-none"
                                                />
                                            </FieldRow>

                                            <div className="grid grid-cols-2 gap-4">
                                                <FieldRow label="Day Shift" error={errors.emp_shift}>
                                                    <ShiftSelect
                                                        value={data.emp_shift}
                                                        onChange={(val) => setData("emp_shift", val)}
                                                    />
                                                </FieldRow>
                                                <FieldRow label="Date" error={errors.sdh_date}>
                                                    <DatePicker
                                                        value={parseDate(data.sdh_date)}
                                                        onChange={(d) =>
                                                            setData("sdh_date", d ? format(d, "yyyy-MM-dd") : "")
                                                        }
                                                        placeholder="Select date…"
                                                    />
                                                </FieldRow>
                                            </div>
                                        </>
                                    )}

                                    {/* Rest */}
                                    {isRest && (
                                        <>
                                            <FieldRow label="Diagnosis Details" error={errors.emp_diagnose}>
                                                <Textarea
                                                    placeholder="Enter diagnosis details…"
                                                    rows={3}
                                                    value={data.emp_diagnose}
                                                    onChange={(e) => setData("emp_diagnose", e.target.value)}
                                                    className="resize-none"
                                                />
                                            </FieldRow>

                                            <div className="grid grid-cols-3 gap-4">
                                                <FieldRow label="Date" error={errors.rest_date}>
                                                    <DatePicker
                                                        value={parseDate(data.rest_date)}
                                                        onChange={(d) =>
                                                            setData("rest_date", d ? format(d, "yyyy-MM-dd") : "")
                                                        }
                                                        placeholder="Select date…"
                                                    />
                                                </FieldRow>
                                                <FieldRow label="Time In (SDH Time In)" error={errors.rest_time_in}>
                                                    <Input
                                                        type="time"
                                                        value={data.rest_time_in}
                                                        onChange={(e) => setData("rest_time_in", e.target.value)}
                                                    />
                                                </FieldRow>
                                                <FieldRow label="Time Out">
                                                    <Input
                                                        type="time"
                                                        readOnly
                                                        value=""
                                                        className="bg-muted/50 cursor-default"
                                                        tabIndex={-1}
                                                    />
                                                    <p className="text-[11px] text-muted-foreground mt-1">
                                                        Set when employee returns
                                                    </p>
                                                </FieldRow>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Form actions */}
                        <div className="flex justify-end gap-3 pt-1">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing || !data.recommendation}>
                                {processing ? "Submitting…" : "Submit Record"}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
