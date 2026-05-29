import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type {
  AnalysisJob,
  ApiError,
  DashboardSummary,
  ExceptionRecord,
  GovernanceActor,
  InspectionTask,
  ListMeta,
  ProfileRecord,
  Sample,
  SampleResult,
  SettingsRecord,
  UserRecord,
} from '../../api/client';
import { oceanApi, setApiAuthToken } from '../../api/client';
import type {
  AnalysisJobForm,
  ExceptionForm,
  LoadState,
  LoginForm,
  Notice,
  ProfileForm,
  ResourceType,
  ResultForm,
  SampleForm,
  SettingsForm,
  UserCreateForm,
  UserEditForm,
  UserFilters,
} from './types';
import {
  emptySummary,
  initialAnalysisJobForm,
  initialExceptionForm,
  initialLoginForm,
  initialProfileForm,
  initialResultForm,
  initialSampleForm,
  initialSettingsForm,
  initialUserCreateForm,
  initialUserEditForm,
  initialUserFilters,
} from './types';
import { formatError, optionalText, parseJsonObject } from './utils';

const AUTH_TOKEN_STORAGE_KEY = 'ocean-auth-token';
const AUTH_ACTOR_STORAGE_KEY = 'ocean-auth-actor';

function readStoredActor(): GovernanceActor | null {
  const raw = localStorage.getItem(AUTH_ACTOR_STORAGE_KEY);

  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw) as GovernanceActor;
  } catch {
    localStorage.removeItem(AUTH_ACTOR_STORAGE_KEY);
    return null;
  }
}

export function useWorkspace() {
  const { t } = useTranslation();

  const actorOptions = useMemo<GovernanceActor[]>(
    () => [
      {
        id: 2,
        username: 'inspector01',
        display_name: 'inspector01',
        roles: ['inspector'],
      },
      {
        id: 3,
        username: 'analyst01',
        display_name: 'analyst01',
        roles: ['analyst'],
      },
      {
        id: 1,
        username: 'admin',
        display_name: 'admin',
        roles: ['admin'],
      },
    ],
    [],
  );

  const [summary, setSummary] = useState<DashboardSummary>(emptySummary);
  const [tasks, setTasks] = useState<InspectionTask[]>([]);
  const [samples, setSamples] = useState<Sample[]>([]);
  const [results, setResults] = useState<SampleResult[]>([]);
  const [exceptions, setExceptions] = useState<ExceptionRecord[]>([]);
  const [analysisJobs, setAnalysisJobs] = useState<AnalysisJob[]>([]);
  const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
  const [selectedTask, setSelectedTask] = useState<InspectionTask | null>(null);
  const [selectedSampleId, setSelectedSampleId] = useState<number | null>(null);
  const [selectedSample, setSelectedSample] = useState<Sample | null>(null);
  const [selectedActorId, setSelectedActorId] = useState(1);
  const [authToken, setAuthToken] = useState<string | null>(() => localStorage.getItem(AUTH_TOKEN_STORAGE_KEY));
  const [currentActor, setCurrentActor] = useState<GovernanceActor | null>(() => readStoredActor());
  const [loadState, setLoadState] = useState<LoadState>('idle');
  const [notice, setNotice] = useState<Notice | null>(null);
  const [sampleForm, setSampleForm] = useState<SampleForm>(initialSampleForm);
  const [resultForm, setResultForm] = useState<ResultForm>(initialResultForm);
  const [exceptionForm, setExceptionForm] = useState<ExceptionForm>(initialExceptionForm);
  const [analysisJobForm, setAnalysisJobForm] = useState<AnalysisJobForm>(initialAnalysisJobForm);
  const [loginForm, setLoginForm] = useState<LoginForm>(initialLoginForm);
  const [profile, setProfile] = useState<ProfileRecord | null>(null);
  const [settings, setSettings] = useState<SettingsRecord | null>(null);
  const [profileForm, setProfileForm] = useState<ProfileForm>(initialProfileForm);
  const [settingsForm, setSettingsForm] = useState<SettingsForm>(initialSettingsForm);
  const [users, setUsers] = useState<UserRecord[]>([]);
  const [usersMeta, setUsersMeta] = useState<ListMeta>({ page: 1, page_size: 20, total: 0 });
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null);
  const [selectedUser, setSelectedUser] = useState<UserRecord | null>(null);
  const [userFilters, setUserFilters] = useState<UserFilters>(initialUserFilters);
  const [userCreateForm, setUserCreateForm] = useState<UserCreateForm>(initialUserCreateForm);
  const [userEditForm, setUserEditForm] = useState<UserEditForm>(initialUserEditForm);
  const [usersForbidden, setUsersForbidden] = useState(false);

  const selectedActor = useMemo(
    () => actorOptions.find((actor) => actor.id === selectedActorId) ?? actorOptions[0],
    [actorOptions, selectedActorId],
  );

  const effectiveActor = currentActor ?? selectedActor;
  const effectiveActorId = effectiveActor.id;
  const isAuthenticated = Boolean(authToken && currentActor);
  const isAdmin = effectiveActor.roles.includes('admin');

  useEffect(() => {
    setApiAuthToken(authToken);

    return () => {
      setApiAuthToken(null);
    };
  }, [authToken]);

  useEffect(() => {
    if (authToken) {
      localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, authToken);
    } else {
      localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
    }
  }, [authToken]);

  useEffect(() => {
    if (currentActor) {
      localStorage.setItem(AUTH_ACTOR_STORAGE_KEY, JSON.stringify(currentActor));
      setSelectedActorId(currentActor.id);
    } else {
      localStorage.removeItem(AUTH_ACTOR_STORAGE_KEY);
    }
  }, [currentActor]);

  const refreshWorkspace = useCallback(async () => {
    setLoadState('loading');
    setNotice(null);

    try {
      const [nextSummary, taskList, sampleList, exceptionList, analysisJobList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listInspectionTasks(),
        oceanApi.listSamples(),
        oceanApi.listExceptions(),
        oceanApi.listAnalysisJobs(),
      ]);

      setSummary(nextSummary);
      setTasks(taskList.data);
      setSamples(sampleList.data);
      setExceptions(exceptionList.data);
      setAnalysisJobs(analysisJobList.data);
      setSelectedTaskId((current) => current ?? taskList.data[0]?.id ?? null);
      setSelectedSampleId((current) => current ?? sampleList.data[0]?.id ?? null);
      setLoadState('ready');
    } catch (error) {
      setLoadState('error');
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }, [t]);

  useEffect(() => {
    void refreshWorkspace();
  }, [refreshWorkspace]);

  useEffect(() => {
    if (!authToken) {
      return;
    }

    let ignore = false;

    oceanApi
      .me()
      .then(({ actor }) => {
        if (!ignore) {
          setCurrentActor(actor);
        }
      })
      .catch((error: ApiError) => {
        if (ignore) {
          return;
        }

        if (error.status === 401) {
          setAuthToken(null);
          setCurrentActor(null);
          setNotice({ tone: 'error', message: formatError(error, t('notices.authExpired')) });
          return;
        }

        setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
      });

    return () => {
      ignore = true;
    };
  }, [authToken, t]);

  const refreshSettings = useCallback(async () => {
    if (!authToken) return;

    try {
      const [nextProfile, nextSettings] = await Promise.all([oceanApi.getProfile(), oceanApi.getSettings()]);

      setProfile(nextProfile);
      setSettings(nextSettings);
      setProfileForm({
        display_name: nextProfile.display_name ?? '',
        email: nextProfile.email ?? '',
      });
      setSettingsForm({
        language: nextSettings.language === 'en' ? 'en' : 'zh-Hans',
        display_density: nextSettings.display_density === 'compact' ? 'compact' : 'comfortable',
        default_workspace_tab: (nextSettings.default_workspace_tab as SettingsForm['default_workspace_tab']) || 'overview',
      });
    } catch {
      setProfile(null);
      setSettings(null);
    }
  }, [authToken, t]);

  useEffect(() => {
    void refreshSettings();
  }, [refreshSettings]);

  useEffect(() => {
    if (!selectedTaskId) {
      setSelectedTask(null);
      return;
    }

    let ignore = false;
    oceanApi
      .getInspectionTask(selectedTaskId)
      .then((task) => {
        if (!ignore) setSelectedTask(task);
      })
      .catch((error) => setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) }));

    return () => {
      ignore = true;
    };
  }, [selectedTaskId, t]);

  useEffect(() => {
    if (!selectedSampleId) {
      setSelectedSample(null);
      setResults([]);
      return;
    }

    let ignore = false;
    Promise.all([oceanApi.getSample(selectedSampleId), oceanApi.listSampleResults(selectedSampleId)])
      .then(([sample, resultList]) => {
        if (!ignore) {
          setSelectedSample(sample);
          setResults(resultList.data);
        }
      })
      .catch((error) => setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) }));

    return () => {
      ignore = true;
    };
  }, [selectedSampleId, t]);

  const refreshUsers = useCallback(async (page = 1) => {
    if (!authToken) return;
    if (!isAdmin) {
      setUsersForbidden(true);
      setUsers([]);
      setSelectedUserId(null);
      setSelectedUser(null);
      return;
    }

    try {
      setUsersForbidden(false);
      const list = await oceanApi.listUsers({
        page,
        page_size: usersMeta.page_size || 20,
        search: userFilters.search.trim() || undefined,
        status: userFilters.status || undefined,
        role: userFilters.role || undefined,
      });
      setUsers(list.data);
      setUsersMeta(list.meta);
      setSelectedUserId((current) => current ?? list.data[0]?.id ?? null);
    } catch (error) {
      if ((error as ApiError).status === 403) {
        setUsersForbidden(true);
        setUsers([]);
        setSelectedUserId(null);
        setSelectedUser(null);
        return;
      }
      setUsers([]);
      setSelectedUserId(null);
      setSelectedUser(null);
    }
  }, [authToken, isAdmin, t, userFilters.role, userFilters.search, userFilters.status, usersMeta.page_size]);

  useEffect(() => {
    void refreshUsers(1);
  }, [refreshUsers]);

  useEffect(() => {
    if (!selectedUserId || usersForbidden) {
      setSelectedUser(null);
      return;
    }

    let ignore = false;
    oceanApi
      .getUser(selectedUserId)
      .then((user) => {
        if (!ignore) {
          setSelectedUser(user);
          setUserEditForm({
            display_name: user.display_name ?? '',
            email: user.email ?? '',
            status: user.status || 'active',
            password: '',
            roles: user.roles ?? [],
          });
        }
      })
      .catch(() => setSelectedUser(null));

    return () => {
      ignore = true;
    };
  }, [selectedUserId, t, usersForbidden]);

  const activeExceptions = useMemo(
    () => exceptions.filter((exception) => exception.status === 'open'),
    [exceptions],
  );

  const activeAnalysisJobs = useMemo(
    () => analysisJobs.filter((job) => job.status === 'queued' || job.status === 'running'),
    [analysisJobs],
  );

  async function handleTaskAction(action: 'start' | 'submit') {
    if (!selectedTaskId) return;

    try {
      if (action === 'start') {
        await oceanApi.startInspectionTask(selectedTaskId, effectiveActorId);
      } else {
        await oceanApi.submitInspectionTask(selectedTaskId, effectiveActorId);
      }

      setNotice({ tone: 'success', message: t('notices.taskDone', { action }) });
      await refreshWorkspace();
      setSelectedTaskId(selectedTaskId);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  async function handleCreateSample(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await oceanApi.createSample({
        sample_code: sampleForm.sample_code.trim(),
        sample_type: sampleForm.sample_type.trim(),
        inspection_task_id: selectedTaskId ?? undefined,
        name: optionalText(sampleForm.name),
        location_text: optionalText(sampleForm.location_text),
        collector_id: effectiveActorId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.sampleCreated', { id: created.id }) });
      setSampleForm((current) => ({ ...current, sample_code: '', name: '' }));
      await refreshWorkspace();
      setSelectedSampleId(created.id);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  async function handleCreateResult(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selectedSampleId) return;

    try {
      await oceanApi.createSampleResult(selectedSampleId, {
        result_type: resultForm.result_type.trim(),
        raw_value: parseJsonObject(resultForm.raw_value, t('fields.rawValue'), t),
        normalized_value: parseJsonObject(resultForm.normalized_value, t('fields.normalizedValue'), t),
        conclusion: optionalText(resultForm.conclusion),
        entered_by: effectiveActorId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.resultSaved') });
      const [sample, resultList, nextSummary] = await Promise.all([
        oceanApi.getSample(selectedSampleId),
        oceanApi.listSampleResults(selectedSampleId),
        oceanApi.getDashboardSummary(),
      ]);

      setSelectedSample(sample);
      setResults(resultList.data);
      setSummary(nextSummary);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  async function handleResolveException(exceptionId: number) {
    try {
      await oceanApi.resolveException(exceptionId, effectiveActorId);
      setNotice({ tone: 'success', message: t('notices.exceptionResolved', { id: exceptionId }) });

      const [nextSummary, exceptionList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listExceptions(),
      ]);

      setSummary(nextSummary);
      setExceptions(exceptionList.data);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  async function handleCreateException(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await oceanApi.createException({
        resource_type: exceptionForm.resource_type,
        resource_id: Number(exceptionForm.resource_id),
        category: exceptionForm.category.trim(),
        severity: optionalText(exceptionForm.severity),
        title: exceptionForm.title.trim(),
        description: optionalText(exceptionForm.description),
        reported_by: effectiveActorId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.exceptionOpened', { id: created.id }) });
      setExceptionForm((current) => ({ ...current, title: '', description: '' }));

      const [nextSummary, exceptionList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listExceptions(),
      ]);

      setSummary(nextSummary);
      setExceptions(exceptionList.data);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  async function handleCreateAnalysisJob(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await oceanApi.createAnalysisJob({
        sample_id: Number(analysisJobForm.sample_id),
        job_type: analysisJobForm.job_type.trim(),
        queued_by: effectiveActorId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.analysisQueued', { id: created.id }) });
      await refreshWorkspace();
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleAnalysisJobAction(jobId: number, action: 'cancel' | 'retry') {
    try {
      if (action === 'cancel') {
        await oceanApi.cancelAnalysisJob(jobId);
      } else {
        await oceanApi.retryAnalysisJob(jobId);
      }

      setNotice({ tone: 'success', message: t('notices.analysisActionDone', { action }) });
      await refreshWorkspace();
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleSaveProfile(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const nextProfile = await oceanApi.updateProfile({
        display_name: optionalText(profileForm.display_name),
        email: optionalText(profileForm.email),
      });
      setProfile(nextProfile);
      setCurrentActor((current) => current ? { ...current, display_name: nextProfile.display_name } : current);
      setNotice({ tone: 'success', message: t('notices.profileSaved') });
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleSaveSettings(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const nextSettings = await oceanApi.updateSettings(settingsForm);
      setSettings(nextSettings);
      setNotice({ tone: 'success', message: t('notices.settingsSaved') });
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleCreateUser(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await oceanApi.createUser({
        username: userCreateForm.username.trim(),
        display_name: userCreateForm.display_name.trim(),
        email: optionalText(userCreateForm.email),
        status: userCreateForm.status,
        password: userCreateForm.password,
        roles: userCreateForm.roles,
      });
      setNotice({ tone: 'success', message: t('notices.userCreated', { username: created.username }) });
      setUserCreateForm(initialUserCreateForm);
      await refreshUsers(1);
      setSelectedUserId(created.id);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  async function handleUpdateUser(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selectedUserId) return;

    try {
      await oceanApi.updateUser(selectedUserId, {
        display_name: optionalText(userEditForm.display_name),
        email: optionalText(userEditForm.email),
        status: userEditForm.status,
        password: optionalText(userEditForm.password) ?? undefined,
      });
      const updated = await oceanApi.replaceUserRoles(selectedUserId, userEditForm.roles);
      setSelectedUser(updated);
      setNotice({ tone: 'success', message: t('notices.userSaved') });
      await refreshUsers(usersMeta.page);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  async function handleUserActivation(action: 'activate' | 'deactivate') {
    if (!selectedUserId) return;

    try {
      const updated = action === 'activate'
        ? await oceanApi.activateUser(selectedUserId)
        : await oceanApi.deactivateUser(selectedUserId);
      setSelectedUser(updated);
      setNotice({ tone: 'success', message: t(action === 'activate' ? 'notices.userActivated' : 'notices.userDeactivated') });
      await refreshUsers(usersMeta.page);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.forbidden')) });
    }
  }

  function setExceptionTarget(resourceType: ResourceType, resourceId: number | null) {
    setExceptionForm((current) => ({
      ...current,
      resource_type: resourceType,
      resource_id: resourceId?.toString() ?? '',
    }));
  }

  function setAnalysisJobTarget(sampleId: number | null, jobType = 'quality_assessment') {
    setAnalysisJobForm({
      sample_id: sampleId?.toString() ?? '',
      job_type: jobType,
    });
  }

  async function handleLogin(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const response = await oceanApi.login({
        username: loginForm.username.trim(),
        password: loginForm.password,
      });

      setAuthToken(response.token);
      setCurrentActor(response.actor);
      setApiAuthToken(response.token);
      setNotice({ tone: 'success', message: t('notices.loginSuccess', { username: response.actor.username }) });
      await refreshWorkspace();
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.loginFailed')) });
    }
  }

  async function handleLogout() {
    try {
      if (authToken) {
        await oceanApi.logout();
      }
    } catch {
      // Continue clearing local auth state even if revoke fails.
    } finally {
      setAuthToken(null);
      setCurrentActor(null);
      setApiAuthToken(null);
      setSummary(emptySummary);
      setTasks([]);
      setSamples([]);
      setResults([]);
      setExceptions([]);
      setAnalysisJobs([]);
      setProfile(null);
      setSettings(null);
      setUsers([]);
      setSelectedUser(null);
      setSelectedUserId(null);
      setSelectedTask(null);
      setSelectedTaskId(null);
      setSelectedSample(null);
      setSelectedSampleId(null);
      setLoadState('idle');
      setNotice({ tone: 'success', message: t('notices.logoutSuccess') });
    }
  }

  return {
    authToken,
    currentActor,
    effectiveActor,
    isAuthenticated,
    isAdmin,
    summary,
    tasks,
    samples,
    results,
    exceptions,
    analysisJobs,
    profile,
    settings,
    users,
    usersMeta,
    selectedTaskId,
    selectedTask,
    selectedSampleId,
    selectedSample,
    selectedActorId,
    selectedActor,
    actorOptions,
    loadState,
    notice,
    loginForm,
    sampleForm,
    resultForm,
    exceptionForm,
    analysisJobForm,
    profileForm,
    settingsForm,
    userFilters,
    userCreateForm,
    userEditForm,
    selectedUserId,
    selectedUser,
    usersForbidden,
    activeExceptions,
    activeAnalysisJobs,
    setSelectedTaskId,
    setSelectedSampleId,
    setSelectedActorId,
    setLoginForm,
    setSampleForm,
    setResultForm,
    setExceptionForm,
    setAnalysisJobForm,
    setProfileForm,
    setSettingsForm,
    setUserFilters,
    setUserCreateForm,
    setUserEditForm,
    setSelectedUserId,
    refreshWorkspace,
    refreshSettings,
    refreshUsers,
    handleLogin,
    handleLogout,
    handleTaskAction,
    handleCreateSample,
    handleCreateResult,
    handleResolveException,
    handleCreateException,
    handleCreateAnalysisJob,
    handleAnalysisJobAction,
    handleSaveProfile,
    handleSaveSettings,
    handleCreateUser,
    handleUpdateUser,
    handleUserActivation,
    setExceptionTarget,
    setAnalysisJobTarget,
  };
}
